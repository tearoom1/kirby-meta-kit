<?php

namespace TearoomOne;

use Kirby\Cms\App;
use Kirby\Http\Remote;
use Kirby\Plugin\License as KirbyLicense;
use Kirby\Plugin\LicenseStatus;
use Kirby\Plugin\Plugin;

/**
 * Meta-Kit License integration with Kirby 5
 *
 * This class extends Kirby's native License class to show
 * license status in the Panel's plugins table.
 */
class MetaKitLicense extends KirbyLicense
{
    private $license;

    public function __construct(
        protected Plugin $plugin,
        protected string $name,
        protected string $id,
        protected string $url
    )
    {
        $this->license = $this->getLicenseFromDisk();

        parent::__construct($plugin,
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
            return null; // invalid
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
        if ($licenseCache->exists('license')) {
            $this->license = $licenseCache->get('license');
            return $this->isValidLicenseKey();
        } else {
            $license = $this->validate();
            if ($license) {
                $this->license = $license;
                $licenseCache->set('license', $this->license, 60 * 24);
                return $this->isValidLicenseKey();
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isValidLicenseKey(): bool
    {
        return $this->license['status'] === 'active' &&
            ($this->license['expires_at'] === null
                || $this->license['expires_at'] > date('c'));
    }

    /**
     * @param $key
     * @return LicenseStatus
     * @throws \Exception
     */
    public function validate(): array|null
    {
        if ($this->license === null) {
            return null;
        }
        $licenseKey = $this->license['key'];

        $requestData = ['license_key' => $licenseKey];
        $response = $this->postLicenseRequest(
            'https://api.lemonsqueezy.com/v1/licenses/validate',
            $requestData);

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
        $uri = kirby()->url();
        // get domain from uri
        $domain = parse_url($uri, PHP_URL_HOST);
        $instanceName = $domain;
        $requestData = [
            'license_key' => $licenseKey,
            'instance_name' => $instanceName
        ];
        $response = $this->postLicenseRequest(
            'https://api.lemonsqueezy.com/v1/licenses/activate',
            $requestData);

        if ($response->code() >= 500) {
            // server error
            throw new \Exception('Server error');
        }

        $data = json_decode($response->content(), true);

        if ($response->code() !== 200) {
            $error = $data['error'];
            if ($error === 'license_key not found.') {
                $error = 'This license key is not valid';
            }
            throw new \Exception($error);
        }


        if ($data['license_key']['status'] === 'active') {
            file_put_contents(self::getLicenseFile(), json_encode($data['license_key']));
            return [
                'success' => true,
                'message' => 'License valid'
            ];
        }
        throw new \Exception('License invalid');
    }

    /**
     * @param mixed $licenseKey
     * @param string $url
     * @return Remote
     * @throws \Exception
     */
    public function postLicenseRequest(string $url, array $requestData): Remote
    {
        $options = [
            'headers' => [
                'Accept: application/json'
            ],
            'method' => 'POST'
        ];
        // request data to get query string
        $queryString = http_build_query($requestData);
        $url .= '?' . $queryString;
        $response = Remote::request($url, $options);
        return $response;
    }

    public function activationDialog()
    {
        // validate license
        $url = $this->url;
        return [
            'dialogs' => [
                'meta-kit/license-activation' => [
                    'load' => function () use ($url) {
                        return [
                            // what dialog component to use
                            'component' => 'k-form-dialog',
                            'props' => [
                                // field definition for the form dialog
                                'fields' => [
                                    'key' => [
                                        'type' => 'text',
                                        'label' => 'License key',
                                        'required' => true,
                                    ],
                                    'info' => [
                                        'type' => 'info',
                                        'text' => 'You can purchase a license at<br><a target="_blank" href="' . $url . '">' . $url . '</a> ',
                                    ],
                                ],
                                // the prefilled model data
                                'value' => [
                                    'key' => '',
                                ],
                            ]
                        ];
                    },
                    'submit' => function () {
                        // custom dialog submitter action
                        $key = get('key');
                        $plugin = kirby()->plugin('tearoom1/meta-kit');
                        return $plugin->license()->activate($key);
                    }
                ]
            ]
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

    /**
     * @return string
     */
    public function getLicenseFile(): string
    {
        return kirby()->roots()->license() . '.' . $this->id;
    }
}
