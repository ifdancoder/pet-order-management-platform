<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Authentication;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Identity\Application\Authentication\IssuedRefreshToken;
use Modules\Identity\Application\Authentication\RotatedRefreshToken;
use Modules\Identity\Application\Exception\InvalidRefreshToken;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Domain\ValueObject\UserId;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\RefreshTokenModel;
use Psr\Clock\ClockInterface;

final readonly class EloquentRefreshTokenService implements IRefreshTokenService
{
    public function __construct(
        private ConnectionInterface $connection,
        private ClockInterface $clock,
        private int $ttlSeconds,
    ) {
        if ($ttlSeconds < 1) {
            throw new InvalidArgumentException(
                'A positive refresh token TTL is required.',
            );
        }
    }

    public function issue(UserId $userId): IssuedRefreshToken
    {
        return $this->persistToken(
            userId: $userId,
            familyId: Str::uuid7()->toString(),
            issuedAt: $this->clock->now(),
        );
    }

    public function rotate(string $token): RotatedRefreshToken
    {
        $rotatedToken = $this->connection->transaction(
            function () use ($token): ?RotatedRefreshToken {
                $storedToken = RefreshTokenModel::query()
                    ->where('token_hash', $this->hash($token))
                    ->lockForUpdate()
                    ->first();

                if ($storedToken === null) {
                    return null;
                }

                $now = $this->clock->now();

                if (
                    $storedToken->consumed_at !== null
                    || $storedToken->revoked_at !== null
                ) {
                    $this->revokeFamily($storedToken->family_id, $now);

                    return null;
                }

                if ($storedToken->expires_at <= $now) {
                    $storedToken->revoked_at = $now;
                    $storedToken->save();

                    return null;
                }

                $storedToken->consumed_at = $now;
                $storedToken->save();

                $userId = new UserId($storedToken->user_id);

                return new RotatedRefreshToken(
                    userId: $userId,
                    refreshToken: $this->persistToken(
                        userId: $userId,
                        familyId: $storedToken->family_id,
                        issuedAt: $now,
                    ),
                );
            },
        );

        if ($rotatedToken === null) {
            throw InvalidRefreshToken::create();
        }

        return $rotatedToken;
    }

    public function revoke(string $token): void
    {
        $this->connection->transaction(function () use ($token): void {
            $storedToken = RefreshTokenModel::query()
                ->where('token_hash', $this->hash($token))
                ->lockForUpdate()
                ->first();

            if ($storedToken === null) {
                return;
            }

            $this->revokeFamily(
                $storedToken->family_id,
                $this->clock->now(),
            );
        });
    }

    private function persistToken(
        UserId $userId,
        string $familyId,
        DateTimeImmutable $issuedAt,
    ): IssuedRefreshToken {
        $plainToken = $this->generateToken();
        $expiresAt = $issuedAt->add(
            new DateInterval(sprintf('PT%dS', $this->ttlSeconds)),
        );

        RefreshTokenModel::query()->create([
            'id' => Str::uuid7()->toString(),
            'family_id' => $familyId,
            'user_id' => $userId->value(),
            'token_hash' => $this->hash($plainToken),
            'expires_at' => $expiresAt,
            'created_at' => $issuedAt,
        ]);

        return new IssuedRefreshToken(
            token: $plainToken,
            expiresAt: $expiresAt,
        );
    }

    private function revokeFamily(
        string $familyId,
        DateTimeImmutable $revokedAt,
    ): void {
        RefreshTokenModel::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $revokedAt]);
    }

    private function generateToken(): string
    {
        return rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '=',
        );
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
