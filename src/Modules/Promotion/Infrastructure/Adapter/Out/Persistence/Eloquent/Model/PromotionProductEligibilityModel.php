<?php

declare(strict_types=1);

namespace Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class PromotionProductEligibilityModel extends Model
{
    protected $table = 'promotion_product_eligibilities';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'promotion_id',
        'sku',
    ];
}
