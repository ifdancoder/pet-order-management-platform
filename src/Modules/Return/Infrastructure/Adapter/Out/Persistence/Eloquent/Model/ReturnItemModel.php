<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class ReturnItemModel extends Model
{
    protected $table = 'return_items';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'return_request_id',
        'inventory_item_id',
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
}
