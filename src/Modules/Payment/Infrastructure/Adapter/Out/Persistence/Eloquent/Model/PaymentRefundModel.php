<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class PaymentRefundModel extends Model
{
    protected $table = 'payment_refunds';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'return_id',
        'payment_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'provider_refund_id',
        'attempts',
        'available_at',
        'claimed_at',
        'claim_token',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
        ];
    }
}
