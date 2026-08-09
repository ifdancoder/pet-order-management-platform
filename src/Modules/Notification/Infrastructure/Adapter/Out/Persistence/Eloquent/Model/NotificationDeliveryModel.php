<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\NotificationDeliveryModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NotificationDeliveryModel extends Model
{
    /** @use HasFactory<NotificationDeliveryModelFactory> */
    use HasFactory;

    protected $table = 'notification_deliveries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'source_message_id',
        'recipient',
        'channel',
        'template',
        'data',
        'status',
        'attempts',
        'available_at',
        'claimed_at',
        'claim_token',
        'last_error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): NotificationDeliveryModelFactory
    {
        return NotificationDeliveryModelFactory::new();
    }
}
