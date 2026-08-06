<?php

declare(strict_types=1);

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Modules\Identity\Application\Exception\InvalidAccessToken;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\PasswordHash;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Authentication\LcobucciAccessTokenService;
use Psr\Clock\ClockInterface;

function identityJwtConfiguration(): Configuration
{
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    if ($key === false || ! openssl_pkey_export($key, $privateKey)) {
        throw new RuntimeException('Unable to generate the test private key.');
    }

    $details = openssl_pkey_get_details($key);

    if ($details === false) {
        throw new RuntimeException('Unable to read the test public key.');
    }

    return Configuration::forAsymmetricSigner(
        signer: new Sha256,
        signingKey: InMemory::plainText($privateKey),
        verificationKey: InMemory::plainText($details['key']),
    );
}

function identityJwtClock(string $time): ClockInterface
{
    return new readonly class($time) implements ClockInterface
    {
        public function __construct(private string $time) {}

        public function now(): DateTimeImmutable
        {
            return new DateTimeImmutable($this->time);
        }
    };
}

function identityJwtUser(): User
{
    return new User(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash('password-hash'),
        status: UserStatus::Active,
    );
}

function identityAccessTokenService(
    Configuration $configuration,
    string $time,
    string $audience = 'orderflow-api',
): LcobucciAccessTokenService {
    return new LcobucciAccessTokenService(
        configuration: $configuration,
        clock: identityJwtClock($time),
        issuer: 'https://identity.orderflow.test',
        audience: $audience,
        ttlSeconds: 900,
    );
}

it('issues and verifies an RS256 access token', function () {
    $configuration = identityJwtConfiguration();
    $service = identityAccessTokenService(
        $configuration,
        '2026-09-27T10:00:00+00:00',
    );

    $issuedToken = $service->issue(identityJwtUser());
    $claims = $service->verify($issuedToken->token);

    expect($claims->userId)->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16')
        ->and($claims->tokenId)->not->toBeEmpty()
        ->and($claims->expiresAt->format(DATE_ATOM))
        ->toBe('2026-09-27T10:15:00+00:00')
        ->and($issuedToken->expiresAt)->toEqual($claims->expiresAt);
});

it('rejects an expired access token', function () {
    $configuration = identityJwtConfiguration();
    $issuedToken = identityAccessTokenService(
        $configuration,
        '2026-09-27T10:00:00+00:00',
    )->issue(identityJwtUser());
    $verifier = identityAccessTokenService(
        $configuration,
        '2026-09-27T10:15:01+00:00',
    );

    $verifier->verify($issuedToken->token);
})->throws(InvalidAccessToken::class);

it('rejects a token with a different audience', function () {
    $configuration = identityJwtConfiguration();
    $issuedToken = identityAccessTokenService(
        $configuration,
        '2026-09-27T10:00:00+00:00',
    )->issue(identityJwtUser());
    $verifier = identityAccessTokenService(
        $configuration,
        '2026-09-27T10:00:00+00:00',
        'another-api',
    );

    $verifier->verify($issuedToken->token);
})->throws(InvalidAccessToken::class);

it('rejects a token with a modified signature', function () {
    $configuration = identityJwtConfiguration();
    $service = identityAccessTokenService(
        $configuration,
        '2026-09-27T10:00:00+00:00',
    );
    $issuedToken = $service->issue(identityJwtUser());
    $lastCharacter = substr($issuedToken->token, -1);
    $modifiedToken = substr($issuedToken->token, 0, -1)
        .($lastCharacter === 'a' ? 'b' : 'a');

    $service->verify($modifiedToken);
})->throws(InvalidAccessToken::class);

it('rejects an empty access token', function () {
    $service = identityAccessTokenService(
        identityJwtConfiguration(),
        '2026-09-27T10:00:00+00:00',
    );

    $service->verify('');
})->throws(InvalidAccessToken::class);
