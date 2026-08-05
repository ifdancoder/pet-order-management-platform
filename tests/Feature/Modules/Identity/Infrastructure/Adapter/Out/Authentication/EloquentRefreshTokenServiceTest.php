<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Exception\InvalidRefreshToken;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Authentication\EloquentRefreshTokenService;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Psr\Clock\ClockInterface;

uses(LazilyRefreshDatabase::class);

function identityRefreshTokenService(
    string $time,
    int $ttlSeconds = 2_592_000,
): EloquentRefreshTokenService {
    $clock = new readonly class($time) implements ClockInterface
    {
        public function __construct(private string $time) {}

        public function now(): DateTimeImmutable
        {
            return new DateTimeImmutable($this->time);
        }
    };

    return new EloquentRefreshTokenService(
        connection: app(ConnectionInterface::class),
        clock: $clock,
        ttlSeconds: $ttlSeconds,
    );
}

it('stores only the hash of an issued refresh token', function () {
    $user = UserModel::factory()->create();
    $service = identityRefreshTokenService('2026-09-27T10:00:00+00:00');

    $issuedToken = $service->issue(new UserId((string) $user->getKey()));

    expect($issuedToken->token)->toHaveLength(43)
        ->and($issuedToken->expiresAt->format(DATE_ATOM))
        ->toBe('2026-10-27T10:00:00+00:00');
    $this->assertDatabaseHas('identity_refresh_tokens', [
        'user_id' => $user->getKey(),
        'token_hash' => hash('sha256', $issuedToken->token),
    ]);
    $this->assertDatabaseMissing('identity_refresh_tokens', [
        'token_hash' => $issuedToken->token,
    ]);
});

it('rotates a refresh token inside the same family', function () {
    $user = UserModel::factory()->create();
    $service = identityRefreshTokenService('2026-09-27T10:00:00+00:00');
    $issuedToken = $service->issue(new UserId((string) $user->getKey()));
    $storedToken = RefreshTokenModel::query()->firstOrFail();

    $rotatedToken = $service->rotate($issuedToken->token);

    expect($rotatedToken->userId->value())->toBe($user->getKey())
        ->and($rotatedToken->refreshToken->token)->not->toBe($issuedToken->token);
    $this->assertDatabaseHas('identity_refresh_tokens', [
        'id' => $storedToken->getKey(),
        'family_id' => $storedToken->family_id,
        'consumed_at' => '2026-09-27 10:00:00',
    ]);
    $this->assertDatabaseHas('identity_refresh_tokens', [
        'family_id' => $storedToken->family_id,
        'token_hash' => hash('sha256', $rotatedToken->refreshToken->token),
        'revoked_at' => null,
    ]);
});

it('revokes the token family when a consumed token is reused', function () {
    $user = UserModel::factory()->create();
    $service = identityRefreshTokenService('2026-09-27T10:00:00+00:00');
    $issuedToken = $service->issue(new UserId((string) $user->getKey()));
    $rotatedToken = $service->rotate($issuedToken->token);

    expect(fn () => $service->rotate($issuedToken->token))
        ->toThrow(InvalidRefreshToken::class);
    expect(RefreshTokenModel::query()->whereNull('revoked_at')->count())
        ->toBe(0);
    expect(fn () => $service->rotate($rotatedToken->refreshToken->token))
        ->toThrow(InvalidRefreshToken::class);
});

it('rejects and revokes an expired refresh token', function () {
    $user = UserModel::factory()->create();
    $issuedToken = identityRefreshTokenService(
        '2026-09-27T10:00:00+00:00',
        60,
    )->issue(new UserId((string) $user->getKey()));
    $service = identityRefreshTokenService(
        '2026-09-27T10:01:01+00:00',
        60,
    );

    expect(fn () => $service->rotate($issuedToken->token))
        ->toThrow(InvalidRefreshToken::class);
    expect(RefreshTokenModel::query()->whereNotNull('revoked_at')->count())
        ->toBe(1);
});

it('revokes a refresh token family idempotently', function () {
    $user = UserModel::factory()->create();
    $service = identityRefreshTokenService('2026-09-27T10:00:00+00:00');
    $issuedToken = $service->issue(new UserId((string) $user->getKey()));

    $service->revoke($issuedToken->token);
    $service->revoke($issuedToken->token);

    expect(RefreshTokenModel::query()->whereNotNull('revoked_at')->count())
        ->toBe(1);
});
