<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateNetworkLicenseKeys extends Command
{
    protected $signature = 'netwatch:keys';

    protected $description = 'Generate NetWatch signing keys without overwriting an existing key pair';

    public function handle(): int
    {
        $privatePath = config('network-licenses.private_key_path');
        $publicPath = config('network-licenses.public_key_path');
        if (File::exists($privatePath) || File::exists($publicPath)) {
            $this->error('Signing keys already exist. They were not overwritten.');

            return self::FAILURE;
        }
        $options = ['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => config_path('network-license-openssl.cnf')];
        $key = openssl_pkey_new($options);
        if (! $key || ! openssl_pkey_export($key, $private, null, $options)) {
            $this->error('Could not generate the signing key.');

            return self::FAILURE;
        }
        File::ensureDirectoryExists(dirname($privatePath), 0700);
        File::ensureDirectoryExists(dirname($publicPath), 0700);
        File::put($privatePath, $private);
        chmod($privatePath, 0600);
        File::put($publicPath, openssl_pkey_get_details($key)['key']);
        $this->info('Signing keys generated. Keep the private key on the server and distribute only the public verification key.');

        return self::SUCCESS;
    }
}
