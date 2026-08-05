<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Controller;

use Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenCommand;
use Modules\Identity\Application\Command\RefreshAccessToken\RefreshAccessTokenHandler;
use Modules\Identity\Presentation\Http\V1\Request\RefreshTokenRequest;
use Modules\Identity\Presentation\Http\V1\Resource\TokenPairResource;

final readonly class RefreshAccessTokenController
{
    public function __construct(
        private RefreshAccessTokenHandler $handler,
    ) {}

    public function __invoke(RefreshTokenRequest $request): TokenPairResource
    {
        $tokens = ($this->handler)(new RefreshAccessTokenCommand(
            refreshToken: $request->refreshToken(),
        ));

        return new TokenPairResource($tokens);
    }
}
