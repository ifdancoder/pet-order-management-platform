<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;

uses(LazilyRefreshDatabase::class);

it('claims pending deliveries and recovers an expired claim', function (): void {
    $this->travelTo('2026-09-28 07:00:00');
    NotificationDeliveryModel::factory()->create();
    $repository = app(INotificationRepository::class);

    $first = $repository->claimBatch(10, 60);
    $active = $repository->claimBatch(10, 60);
    $this->travel(61)->seconds();
    $recovered = $repository->claimBatch(10, 60);

    expect($first)->toHaveCount(1)
        ->and($first[0]->attempts)->toBe(1)
        ->and($active)->toBe([])
        ->and($recovered)->toHaveCount(1)
        ->and($recovered[0]->attempts)->toBe(2)
        ->and($recovered[0]->claimToken)->not->toBe($first[0]->claimToken);
});

it('marks only the currently claimed delivery as sent', function (): void {
    $notification = NotificationDeliveryModel::factory()->create();
    $repository = app(INotificationRepository::class);
    $attempt = $repository->claimBatch(1, 60)[0];

    $repository->markSent($attempt->notificationId, $attempt->claimToken);

    $this->assertDatabaseHas('notification_deliveries', [
        'id' => $notification->getKey(),
        'status' => NotificationStatus::Sent->value,
        'claimed_at' => null,
        'claim_token' => null,
    ]);
});
