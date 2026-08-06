<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\OrderItemModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderItemModel extends Model
{
    /** @use HasFactory<OrderItemModelFactory> */
    use HasFactory;

    protected $table = 'order_items';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'inventory_item_id',
        'sku',
        'quantity',
        'unit_price_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
        ];
    }

    protected static function newFactory(): OrderItemModelFactory
    {
        return OrderItemModelFactory::new();
    }
}
