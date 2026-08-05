<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LogicException;

final class GenerateJwtKeysCommand extends Command
{
    protected $signature = 'identity:generate-jwt-keys
                            {--force : Replace an existing key pair}';

    protected $description = 'Generate the RSA key pair used to sign Identity access tokens';

    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $privateKeyPath = config('identity.authentication.jwt.private_key_path');
        $publicKeyPath = config('identity.authentication.jwt.public_key_path');
        $passphrase = config('identity.authentication.jwt.private_key_passphrase');

        if (
            ! is_string($privateKeyPath)
            || ! is_string($publicKeyPath)
            || ! is_string($passphrase)
        ) {
            throw new LogicException('Identity JWT key configuration is invalid.');
        }

        if (
            ! $this->option('force')
            && ($this->files->exists($privateKeyPath) || $this->files->exists($publicKeyPath))
        ) {
            $this->components->error('JWT keys already exist. Use --force to replace them.');

            return self::FAILURE;
        }

        $key = openssl_pkey_new([
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new LogicException('Unable to generate the JWT RSA key pair.');
        }

        $privateKey = '';

        if (! openssl_pkey_export($key, $privateKey, $passphrase)) {
            throw new LogicException('Unable to export the JWT private key.');
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false) {
            throw new LogicException('Unable to export the JWT public key.');
        }

        $this->files->ensureDirectoryExists(dirname($privateKeyPath));
        $this->files->ensureDirectoryExists(dirname($publicKeyPath));
        $this->files->put($privateKeyPath, $privateKey, true);
        $this->files->put($publicKeyPath, $details['key'], true);
        $this->files->chmod($privateKeyPath, 0600);
        $this->files->chmod($publicKeyPath, 0644);

        $this->components->info('Identity JWT key pair generated.');

        return self::SUCCESS;
    }
}
