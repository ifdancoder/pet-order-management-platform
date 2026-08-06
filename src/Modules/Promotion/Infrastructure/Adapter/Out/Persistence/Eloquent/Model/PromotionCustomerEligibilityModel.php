<?php

declare(strict_types=1);

namespace Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

final class PromotionCustomerEligibilityModel extends Model
{
    protected $table = 'promotion_customer_eligibilities';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'promotion_id',
        'customer_id',
    ];
}
