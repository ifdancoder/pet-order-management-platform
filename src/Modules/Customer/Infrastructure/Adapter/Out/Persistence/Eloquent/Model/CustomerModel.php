<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\CustomerModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CustomerModel extends Model
{
    /** @use HasFactory<CustomerModelFactory> */
    use HasFactory;

    protected $table = 'customers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'identity_user_id',
        'given_name',
        'family_name',
        'phone_number',
        'status',
    ];

    protected static function newFactory(): CustomerModelFactory
    {
        return CustomerModelFactory::new();
    }
}
