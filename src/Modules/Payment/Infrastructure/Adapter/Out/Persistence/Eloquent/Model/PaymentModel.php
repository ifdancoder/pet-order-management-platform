<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\PaymentModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentModel extends Model
{
    /** @use HasFactory<PaymentModelFactory> */
    use HasFactory;

    protected $table = 'payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'amount',
        'currency',
        'provider',
        'status',
        'idempotency_key',
        'request_hash',
        'provider_payment_id',
        'failure_code',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    protected static function newFactory(): PaymentModelFactory
    {
        return PaymentModelFactory::new();
    }
}
