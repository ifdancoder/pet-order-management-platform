<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\ReturnRequestModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReturnRequestModel extends Model
{
    /** @use HasFactory<ReturnRequestModelFactory> */
    use HasFactory;

    protected $table = 'return_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'customer_id',
        'status',
        'reason',
        'currency',
        'refund_amount',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'integer',
            'requested_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<ReturnItemModel, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnItemModel::class, 'return_request_id');
    }

    protected static function newFactory(): ReturnRequestModelFactory
    {
        return ReturnRequestModelFactory::new();
    }
}
