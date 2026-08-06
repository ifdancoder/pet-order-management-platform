<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Middleware;

use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Identity\Application\Exception\InvalidAccessToken;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\ValueObject\UserId;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateAccessToken
{
    public function __construct(
        private IAccessTokenService $accessTokens,
        private IUserRepository $users,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw InvalidAccessToken::create();
        }

        $claims = $this->accessTokens->verify($token);

        try {
            $userId = new UserId($claims->userId);
        } catch (InvalidArgumentException $exception) {
            throw InvalidAccessToken::create($exception);
        }

        $user = $this->users->findById($userId);

        if ($user === null || $user->status() !== UserStatus::Active) {
            throw InvalidAccessToken::create();
        }

        $request->attributes->set('identity.user_id', $user->id()->value());

        return $next($request);
    }
}
