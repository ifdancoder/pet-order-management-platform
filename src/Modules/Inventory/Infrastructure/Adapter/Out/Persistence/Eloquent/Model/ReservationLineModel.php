<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\ReservationLineModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReservationLineModel extends Model
{
    /** @use HasFactory<ReservationLineModelFactory> */
    use HasFactory;

    protected $table = 'inventory_reservation_lines';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'inventory_item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    protected static function newFactory(): ReservationLineModelFactory
    {
        return ReservationLineModelFactory::new();
    }
}
