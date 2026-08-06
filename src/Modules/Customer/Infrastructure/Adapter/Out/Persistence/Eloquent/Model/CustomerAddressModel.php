<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\CustomerAddressModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CustomerAddressModel extends Model
{
    /** @use HasFactory<CustomerAddressModelFactory> */
    use HasFactory;

    protected $table = 'customer_addresses';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'recipient_name',
        'line_1',
        'line_2',
        'city',
        'region',
        'postal_code',
        'country_code',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    protected static function newFactory(): CustomerAddressModelFactory
    {
        return CustomerAddressModelFactory::new();
    }
}
