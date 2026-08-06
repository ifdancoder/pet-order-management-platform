<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\InventoryItemModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InventoryItemModel extends Model
{
    /** @use HasFactory<InventoryItemModelFactory> */
    use HasFactory;

    protected $table = 'inventory_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'sku',
        'on_hand',
        'reserved',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'reserved' => 'integer',
        ];
    }

    protected static function newFactory(): InventoryItemModelFactory
    {
        return InventoryItemModelFactory::new();
    }
}
