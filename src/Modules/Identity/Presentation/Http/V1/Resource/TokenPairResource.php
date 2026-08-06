<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Application\Authentication\TokenPair;

final class TokenPairResource extends JsonResource
{
    private TokenPair $tokenPair;

    public function __construct(TokenPair $resource)
    {
        parent::__construct($resource);

        $this->tokenPair = $resource;
    }

    /**
     * @return array{
     *     access_token: string,
     *     refresh_token: string,
     *     token_type: 'Bearer',
     *     access_expires_at: string,
     *     refresh_expires_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->tokenPair->accessToken->token,
            'refresh_token' => $this->tokenPair->refreshToken->token,
            'token_type' => 'Bearer',
            'access_expires_at' => $this->tokenPair->accessToken->expiresAt->format(DATE_ATOM),
            'refresh_expires_at' => $this->tokenPair->refreshToken->expiresAt->format(DATE_ATOM),
        ];
    }
}
