<?php

namespace TearoomOne;

use Kirby\Cms\App;
use Kirby\Http\Remote;
use Kirby\Plugin\License as KirbyLicense;
use Kirby\Plugin\LicenseStatus;
use Kirby\Plugin\Plugin;

class MetaKitLicense extends KirbyLicense
{
    private $license;

    public function __construct(
        protected Plugin $plugin,
        protected string $name,
        protected string $id,
        protected string $url
    ) {
        $this->license = $this->getLicenseFromDisk();

        parent::__construct(
            $plugin,
            'Meta-Kit License',
            $url,
            $this->getLicenseStatus()
        );
    }

    private function getLicenseStatus(): LicenseStatus|null
    {
        if ($this->isValid()) {
            return LicenseStatus::from('active');
        }

        if (kirby()->system()->isLocal()) {
            $demo = LicenseStatus::from('demo');
            return new LicenseStatus(
                value: $demo->value(),
                icon: $demo->icon(),
                label: $demo->label(),
                dialog: 'meta-kit/license-activation',
                theme: $demo->theme()
            );
        }

        if ($this->license !== null) {
            return null;
        }

        $missing = LicenseStatus::from('missing');
        return new LicenseStatus(
            value: $missing->value(),
            icon: $missing->icon(),
            label: $missing->label(),
            dialog: 'meta-kit/license-activation',
            theme: $missing->theme()
        );
    }

    public function isValid(): bool
    {
        $licenseCache = App::instance()->cache('tearoom1.meta-kit.performer');
        if ($this->license === null) {
            $licenseCache->remove('license');
            return false;
        }

        $currentLicenseKey = $this->license['key'] ?? null;

        if ($licenseCache->exists('license')) {
            $cachedLicense = $licenseCache->get('license');
            if (
                is_array($cachedLicense) &&
                ($cachedLicense['key'] ?? null) === $currentLicenseKey
            ) {
                $this->license = $cachedLicense;
                return $this->isValidLicenseKey();
            }

            $licenseCache->remove('license');
        }

        $license = $this->validate();
        if ($license) {
            $this->license = $license;
            $licenseCache->set('license', $this->license, 60 * 24);
            return $this->isValidLicenseKey();
        }

        return false;
    }

    public function isValidLicenseKey(): bool
    {
        return is_array($this->license) &&
            ($this->license['status'] ?? null) === 'active' &&
            (($this->license['expires_at'] ?? null) === null || $this->license['expires_at'] > date('c'));
    }

    public function validate(): array|null
    {
        if ($this->license === null) {
            return null;
        }

        $licenseKey = $this->license['key'] ?? null;
        if (!$licenseKey) {
            return null;
        }

        $response = $this->postLicenseRequest(
            'https://api.lemonsqueezy.com/v1/licenses/validate',
            ['license_key' => $licenseKey]
        );

        if ($response->code() === 200) {
            $data = json_decode($response->content(), true);
            if (is_array($data) && ($data['valid'] ?? false) === true) {
                return $data['license_key'] ?? null;
            }
        }

        return null;
    }

    public function activate($licenseKey): array
    {
        $domain = parse_url(kirby()->url(), PHP_URL_HOST);
        $response = $this->postLicenseRequest(
            'https://api.lemonsqueezy.com/v1/licenses/activate',
            [
                'license_key' => $licenseKey,
                'instance_name' => $domain,
            ]
        );

        if ($response->code() >= 500) {
            throw new \Exception('Server error');
        }

        $data = json_decode($response->content(), true);

        if ($response->code() !== 200) {
            $error = $data['error'] ?? 'License activation failed';
            if ($error === 'license_key not found.') {
                $error = 'This license key is not valid';
            }
            throw new \Exception($error);
        }

        if (($data['license_key']['status'] ?? null) === 'active') {
            file_put_contents(self::getLicenseFile(), json_encode($data['license_key']));
            App::instance()->cache('tearoom1.meta-kit.performer')->remove('license');
            return [
                'success' => true,
                'message' => 'License valid',
            ];
        }

        throw new \Exception('License invalid');
    }

    public function postLicenseRequest(string $url, array $requestData): Remote
    {
        return Remote::request($url . '?' . http_build_query($requestData), [
            'headers' => [
                'Accept: application/json',
            ],
            'method' => 'POST',
        ]);
    }

    public function activationDialog()
    {
        $url = $this->url;
        return [
            'dialogs' => [
                'meta-kit/license-activation' => [
                    'load' => function () use ($url) {
                        return [
                            'component' => 'k-form-dialog',
                            'props' => [
                                'fields' => [
                                    'key' => [
                                        'type' => 'text',
                                        'label' => 'License key',
                                        'required' => true,
                                    ],
                                    'info' => [
                                        'type' => 'info',
                                        'text' => 'A license is required for AI models that are not on the free test allowlist.<br><a target="_blank" href="' . $url . '">' . $url . '</a>',
                                    ],
                                ],
                                'value' => [
                                    'key' => '',
                                ],
                            ],
                        ];
                    },
                    'submit' => function () {
                        $plugin = kirby()->plugin('tearoom1/meta-kit');
                        return $plugin->license()->activate(get('key'));
                    },
                ],
            ],
        ];
    }

    private function getLicenseFromDisk(): array|null
    {
        $licenseFile = $this->getLicenseFile();
        if (!file_exists($licenseFile)) {
            return null;
        }

        return json_decode(file_get_contents($licenseFile), true);
    }

    public function getLicenseFile(): string
    {
        return kirby()->roots()->license() . '.' . $this->id;
    }
}
