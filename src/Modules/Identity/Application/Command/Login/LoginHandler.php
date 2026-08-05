<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Command\Login;

use Modules\Identity\Application\Authentication\TokenPair;
use Modules\Identity\Application\Exception\InvalidCredentials;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Application\Port\Out\Authentication\IRefreshTokenService;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Security\IPasswordHasher;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\Email;

final readonly class LoginHandler
{
    public function __construct(
        private IUserRepository $users,
        private IPasswordHasher $passwordHasher,
        private IAccessTokenService $accessTokens,
        private IRefreshTokenService $refreshTokens,
    ) {}

    public function __invoke(LoginCommand $command): TokenPair
    {
        $user = $this->users->findByEmail(new Email($command->email));

        if (
            $user === null
            || $user->status() !== UserStatus::Active
            || ! $this->passwordHasher->verify(
                $command->password,
                $user->passwordHash(),
            )
        ) {
            throw InvalidCredentials::create();
        }

        $accessToken = $this->accessTokens->issue($user);
        $refreshToken = $this->refreshTokens->issue($user->id());

        return new TokenPair($accessToken, $refreshToken);
    }
}
