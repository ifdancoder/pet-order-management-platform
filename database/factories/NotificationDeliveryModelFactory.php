<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationStatus;
use Modules\Notification\Domain\Enum\NotificationTemplate;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\NotificationDeliveryModel;

/** @extends Factory<NotificationDeliveryModel> */
final class NotificationDeliveryModelFactory extends Factory
{
    protected $model = NotificationDeliveryModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'source_message_id' => Str::uuid7()->toString(),
            'recipient' => fake()->safeEmail(),
            'channel' => NotificationChannel::Email->value,
            'template' => NotificationTemplate::WelcomeUser->value,
            'data' => ['user_id' => Str::uuid7()->toString()],
            'status' => NotificationStatus::Pending->value,
            'attempts' => 0,
            'available_at' => now(),
        ];
    }
}
