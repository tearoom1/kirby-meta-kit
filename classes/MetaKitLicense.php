<?php

namespace TearoomOne;

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
    public function __construct(
        protected Plugin $plugin,
        protected string $name,
        protected string $id,
        protected string $url
    )
    {
        parent::__construct($plugin,
            'Meta-Kit License',
            $url,
            $this->getLicenseStatus()
        );
    }


    private function getLicense(): array|null
    {
        $licenseFile = kirby()->roots()->license() . '.' . $this->id;
        if (!file_exists($licenseFile)) {
            return null;
        }
        return json_decode(file_get_contents($licenseFile), true);
    }

    private function getLicenseStatus(): LicenseStatus
    {
        $isLocal = kirby()->environment()->isLocal();
        if (false && $isLocal) {
            return new LicenseStatus(
                value: 'missing',
                icon: 'alert',
                label: 'Local environment, no license required',
                theme: 'negative'
            );
        }

        $license = $this->getLicense();

        if (!$license) {
            return new LicenseStatus(
                value: 'missing',
                icon: 'alert',
                label: 'Get a license please',
                dialog: 'meta-kit/license-activation',
                theme: 'negative'
            );
        }

        if ($license['status'] === 'active' && ($license['expires_at'] === null || $license['expires_at'] < date(DATE_ISO8601_EXPANDED))) {
            return new LicenseStatus(
                value: 'valid',
                icon: 'check',
                label: 'Valid license',
                theme: 'positive'
            );
        } else {
            return new LicenseStatus(
                value: 'invalid',
                icon: 'alert',
                label: 'Invalid license',
                theme: 'negative'
            );
        }
    }

    /**
     * @param $key
     * @return LicenseStatus
     * @throws \Exception
     */
    public function validate(): LicenseStatus
    {

        $license = $this->getLicense();

        $licenseKey = $license['key'];

        $requestData = ['license_key' => $licenseKey];
        $response = $this->postLicenseRequest(
            'https://api.lemonsqueezy.com/v1/licenses/validate',
            $requestData);

        if ($response->code() !== 200) { // todo fixme
            return new LicenseStatus(
                value: 'invalid',
                icon: 'alert',
                label: 'Invalid license',
                theme: 'negative'
            );
        }

        $data = json_decode($response->content(), true);

        if ($data['valid'] === true) {
            return new LicenseStatus(
                value: 'valid',
                icon: 'check',
                label: 'Valid license',
                theme: 'positive'
            );
        }

        return new LicenseStatus(
            value: 'invalid',
            icon: 'alert',
            label: 'Invalid license',
            theme: 'negative'
        );
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
}
