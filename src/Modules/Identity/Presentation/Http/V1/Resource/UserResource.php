<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Entity\User;

final class UserResource extends JsonResource
{
    public function __construct(User $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id()->value(),
            'email' => $user->email()->value(),
            'status' => $user->status()->value,
        ];
    }
}
