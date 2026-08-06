<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class CheckoutModel extends Model
{
    protected $table = 'order_checkouts';

    protected $primaryKey = 'idempotency_key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'idempotency_key',
        'request_hash',
        'order_id',
        'inventory_reservation_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
        ];
    }
}
