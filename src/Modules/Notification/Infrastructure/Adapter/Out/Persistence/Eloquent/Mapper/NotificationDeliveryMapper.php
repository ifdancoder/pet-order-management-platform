<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Modules\Notification\Domain\Entity\NotificationDelivery;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;

final class NotificationDeliveryMapper
{
    public function mapToModel(
        NotificationDelivery $notification,
        NotificationDeliveryModel $model,
    ): void {
        $model->id = $notification->id()->value();
        $model->source_message_id = $notification->sourceMessageId();
        $model->recipient = $notification->recipient()->value();
        $model->channel = $notification->channel()->value;
        $model->template = $notification->template()->value;
        $model->setAttribute('data', $notification->data());
        $model->status = $notification->status()->value;
    }
}
