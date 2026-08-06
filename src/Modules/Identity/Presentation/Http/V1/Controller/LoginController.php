<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\Login\LoginCommand;
use Modules\Identity\Presentation\Http\V1\Request\LoginRequest;
use Modules\Identity\Presentation\Http\V1\Resource\TokenPairResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class LoginController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(LoginRequest $request): TokenPairResource
    {
        $tokens = $this->commandBus->dispatch(new LoginCommand(
            email: $request->email(),
            password: $request->password(),
        ));

        return new TokenPairResource($tokens);
    }
}
