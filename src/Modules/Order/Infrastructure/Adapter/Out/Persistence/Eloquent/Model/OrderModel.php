<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\OrderModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'shipping_cost_amount',
        'shipping_method',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'discount_amount' => 'integer',
            'shipping_cost_amount' => 'integer',
        ];
    }

    /** @return HasMany<OrderItemModel, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }

    protected static function newFactory(): OrderModelFactory
    {
        return OrderModelFactory::new();
    }
}
