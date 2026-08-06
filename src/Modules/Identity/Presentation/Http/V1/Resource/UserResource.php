<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Entity\User;

final class UserResource extends JsonResource
{
    private User $user;

    public function __construct(User $resource)
    {
        parent::__construct($resource);

        $this->user = $resource;
    }

    /** @return array{id: string, email: string, status: int} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id()->value(),
            'email' => $this->user->email()->value(),
            'status' => $this->user->status()->value,
        ];
    }
}
