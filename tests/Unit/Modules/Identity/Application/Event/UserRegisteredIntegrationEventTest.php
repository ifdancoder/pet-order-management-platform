<?php

declare(strict_types=1);

use Modules\Identity\Application\Event\UserRegisteredIntegrationEvent;
use Modules\Identity\Domain\Entity\User;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\PasswordHash;
use Modules\Identity\Domain\ValueObject\UserId;

it('exposes only the registered user identity needed by consumers', function (): void {
    $event = UserRegisteredIntegrationEvent::fromUser(User::register(
        id: new UserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4fa0'),
        email: new Email('user@example.com'),
        passwordHash: new PasswordHash(str_repeat('x', 60)),
    ));

    expect($event->name())->toBe('user.registered.v1')
        ->and($event->aggregateId())->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4fa0')
        ->and($event->payload())->toBe([
            'user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fa0',
            'email' => 'user@example.com',
        ]);
});
