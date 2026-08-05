<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Illuminate\Http\Response;
use Modules\Identity\Application\Command\Logout\LogoutCommand;
use Modules\Identity\Application\Command\Logout\LogoutHandler;
use Modules\Identity\Presentation\Http\V1\Request\RefreshTokenRequest;

final readonly class LogoutController
{
    public function __construct(
        private LogoutHandler $handler,
    ) {}

    public function __invoke(RefreshTokenRequest $request): Response
    {
        ($this->handler)(new LogoutCommand(
            refreshToken: $request->refreshToken(),
        ));

        return response()->noContent();
    }
}
