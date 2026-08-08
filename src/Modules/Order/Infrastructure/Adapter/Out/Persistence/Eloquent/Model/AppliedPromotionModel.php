<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class AppliedPromotionModel extends Model
{
    protected $table = 'order_applied_promotions';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'promotion_code',
    ];
}
