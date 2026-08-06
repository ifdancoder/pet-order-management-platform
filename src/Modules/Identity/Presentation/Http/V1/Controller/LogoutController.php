<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Illuminate\Http\Response;
use Modules\Identity\Application\Command\Logout\LogoutCommand;
use Modules\Identity\Presentation\Http\V1\Request\RefreshTokenRequest;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class LogoutController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(RefreshTokenRequest $request): Response
    {
        $this->commandBus->dispatch(new LogoutCommand(
            refreshToken: $request->refreshToken(),
        ));

        return response()->noContent();
    }
}
