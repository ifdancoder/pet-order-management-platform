<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Event;

use Modules\Identity\Domain\Entity\User;
use Shared\Application\Event\IIntegrationEvent;

final readonly class UserRegisteredIntegrationEvent implements IIntegrationEvent
{
    private function __construct(
        private string $userId,
        private string $email,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            userId: $user->id()->value(),
            email: $user->email()->value(),
        );
    }

    public function name(): string
    {
        return 'user.registered.v1';
    }

    public function aggregateId(): string
    {
        return $this->userId;
    }

    /** @return array{user_id: string, email: string} */
    public function payload(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
        ];
    }
}
