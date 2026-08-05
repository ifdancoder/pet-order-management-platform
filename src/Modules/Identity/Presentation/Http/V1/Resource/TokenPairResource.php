<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Application\Authentication\TokenPair;

final class TokenPairResource extends JsonResource
{
    public function __construct(TokenPair $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TokenPair $tokenPair */
        $tokenPair = $this->resource;

        return [
            'access_token' => $tokenPair->accessToken->token,
            'refresh_token' => $tokenPair->refreshToken->token,
            'token_type' => 'Bearer',
            'access_expires_at' => $tokenPair->accessToken->expiresAt->format(DATE_ATOM),
            'refresh_expires_at' => $tokenPair->refreshToken->expiresAt->format(DATE_ATOM),
        ];
    }
}
