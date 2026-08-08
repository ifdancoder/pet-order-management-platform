<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\OrderModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderModel extends Model
{
    /** @use HasFactory<OrderModelFactory> */
    use HasFactory;

    protected $table = 'orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'currency',
        'status',
        'discount_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'discount_amount' => 'integer',
        ];
    }

    protected static function newFactory(): OrderModelFactory
    {
        return OrderModelFactory::new();
    }
}
