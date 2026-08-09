<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Messaging\IntegrationMessageConsumer;

uses(LazilyRefreshDatabase::class);

it('records one welcome email when registration is delivered twice', function (): void {
    $message = userRegisteredNotificationTestMessage();
    $consumer = app(IntegrationMessageConsumer::class);

    expect($consumer->consume('notification-user-registered', $message))->toBeTrue()
        ->and($consumer->consume('notification-user-registered', $message))->toBeFalse();

    $notification = NotificationDeliveryModel::query()->sole();
    expect($notification->recipient)->toBe('user@example.com')
        ->and($notification->status)->toBe(NotificationStatus::Pending->value)
        ->and($notification->data)->toBe([
            'user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc1',
        ]);
    $this->assertDatabaseCount('processed_messages', 1);
});

it('rolls back inbox state for an invalid registration message', function (): void {
    $message = new IntegrationMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc2',
        name: 'user.registered.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc1',
        occurredAt: new DateTimeImmutable('2026-09-28T06:00:00+00:00'),
        data: ['user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc1'],
    );

    expect(fn (): bool => app(IntegrationMessageConsumer::class)->consume(
        'notification-user-registered',
        $message,
    ))->toThrow(InvalidArgumentException::class);

    $this->assertDatabaseCount('notification_deliveries', 0);
    $this->assertDatabaseCount('processed_messages', 0);
});

function userRegisteredNotificationTestMessage(): IntegrationMessage
{
    return new IntegrationMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc0',
        name: 'user.registered.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc1',
        occurredAt: new DateTimeImmutable('2026-09-28T06:00:00+00:00'),
        data: [
            'user_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4fc1',
            'email' => 'User@Example.com',
        ],
    );
}
