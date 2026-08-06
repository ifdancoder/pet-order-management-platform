<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenCommand;
use Modules\Identity\Presentation\Http\V1\Request\RefreshTokenRequest;
use Modules\Identity\Presentation\Http\V1\Resource\TokenPairResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RefreshAccessTokenController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(RefreshTokenRequest $request): TokenPairResource
    {
        $tokens = $this->commandBus->dispatch(new RefreshAccessTokenCommand(
            refreshToken: $request->refreshToken(),
        ));

        return new TokenPairResource($tokens);
    }
}
