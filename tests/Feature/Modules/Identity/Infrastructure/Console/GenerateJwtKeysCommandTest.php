<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->keyDirectory = sys_get_temp_dir().'/orderflow-jwt-'.Str::uuid7();
    $this->privateKeyPath = $this->keyDirectory.'/jwt-private.pem';
    $this->publicKeyPath = $this->keyDirectory.'/jwt-public.pem';

    config()->set(
        'identity.authentication.jwt.private_key_path',
        $this->privateKeyPath,
    );
    config()->set(
        'identity.authentication.jwt.public_key_path',
        $this->publicKeyPath,
    );
});

afterEach(function () {
    File::deleteDirectory($this->keyDirectory);
});

it('generates an RSA key pair without exposing the private key', function () {
    $this->artisan('identity:generate-jwt-keys')
        ->expectsOutputToContain('Identity JWT key pair generated.')
        ->assertSuccessful();

    expect(File::get($this->privateKeyPath))
        ->toContain('PRIVATE KEY')
        ->and(File::get($this->publicKeyPath))
        ->toContain('PUBLIC KEY')
        ->and(fileperms($this->privateKeyPath) & 0777)
        ->toBe(0600);
});

it('does not replace an existing key pair unless forced', function () {
    $this->artisan('identity:generate-jwt-keys')->assertSuccessful();
    $originalPrivateKey = File::get($this->privateKeyPath);

    $this->artisan('identity:generate-jwt-keys')
        ->expectsOutputToContain('JWT keys already exist.')
        ->assertFailed();

    expect(File::get($this->privateKeyPath))->toBe($originalPrivateKey);

    $this->artisan('identity:generate-jwt-keys', ['--force' => true])
        ->assertSuccessful();

    expect(File::get($this->privateKeyPath))->not->toBe($originalPrivateKey);
});
