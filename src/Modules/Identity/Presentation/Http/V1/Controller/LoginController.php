<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\Login\LoginCommand;
use Modules\Identity\Application\Command\Login\LoginHandler;
use Modules\Identity\Presentation\Http\V1\Request\LoginRequest;
use Modules\Identity\Presentation\Http\V1\Resource\TokenPairResource;

final readonly class LoginController
{
    public function __construct(
        private LoginHandler $handler,
    ) {}

    public function __invoke(LoginRequest $request): TokenPairResource
    {
        $tokens = ($this->handler)(new LoginCommand(
            email: $request->email(),
            password: $request->password(),
        ));

        return new TokenPairResource($tokens);
    }
}
