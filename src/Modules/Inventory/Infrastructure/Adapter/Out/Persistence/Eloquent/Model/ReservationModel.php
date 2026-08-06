<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\ReservationModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReservationModel extends Model
{
    /** @use HasFactory<ReservationModelFactory> */
    use HasFactory;

    protected $table = 'inventory_reservations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'reservation_key',
        'status',
    ];

    protected static function newFactory(): ReservationModelFactory
    {
        return ReservationModelFactory::new();
    }
}
