<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\RefreshAccessToken;

use Modules\Identity\Application\Authentication\TokenPair;
use Modules\Identity\Application\Exception\InvalidCredentials;
use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Domain\Enum\UserStatus;
use Throwable;

final readonly class RefreshAccessTokenHandler
{
    public function __construct(
        private IUserRepository $users,
        private IAccessTokenService $accessTokens,
        private IRefreshTokenService $refreshTokens,
    ) {}

    public function __invoke(RefreshAccessTokenCommand $command): TokenPair
    {
        $rotatedToken = $this->refreshTokens->rotate($command->refreshToken);

        try {
            $user = $this->users->findById($rotatedToken->userId);

            if ($user === null) {
                throw UserNotFound::withId(
                    $rotatedToken->userId->value(),
                );
            }

            if ($user->status() !== UserStatus::Active) {
                throw InvalidCredentials::create();
            }

            return new TokenPair(
                accessToken: $this->accessTokens->issue($user),
                refreshToken: $rotatedToken->refreshToken,
            );
        } catch (Throwable $exception) {
            $this->refreshTokens->revoke(
                $rotatedToken->refreshToken->token,
            );

            throw $exception;
        }
    }
}
