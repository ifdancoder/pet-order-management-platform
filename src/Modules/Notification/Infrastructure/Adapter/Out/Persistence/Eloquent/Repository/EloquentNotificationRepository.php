<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Domain\Entity\NotificationDelivery;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\NotificationDeliveryMapper;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;

final readonly class EloquentNotificationRepository implements INotificationRepository
{
    public function __construct(
        private NotificationDeliveryMapper $mapper,
    ) {}

    public function save(NotificationDelivery $notification): void
    {
        $model = NotificationDeliveryModel::query()->find(
            $notification->id()->value(),
        ) ?? new NotificationDeliveryModel;
        $this->mapper->mapToModel($notification, $model);

        if (! $model->exists) {
            $model->setAttribute('available_at', now());
        }

        $model->save();
    }
}
