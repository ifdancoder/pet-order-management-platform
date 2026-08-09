<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\ShipmentModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentModel extends Model
{
    /** @use HasFactory<ShipmentModelFactory> */
    use HasFactory;

    protected $table = 'shipments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'method',
        'address',
        'weight_grams',
        'cost_amount',
        'currency',
        'status',
        'provider_shipment_id',
        'tracking_number',
        'attempts',
        'available_at',
        'claimed_at',
        'claim_token',
        'last_error',
        'booked_at',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'weight_grams' => 'integer',
            'cost_amount' => 'integer',
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'booked_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ShipmentModelFactory
    {
        return ShipmentModelFactory::new();
    }
}
