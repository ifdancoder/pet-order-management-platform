<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Port\Out\Persistence;

use Modules\Promotion\Domain\Entity\Promotion;
use Modules\Promotion\Domain\ValueObject\PromotionCode;

interface IPromotionRepository
{
    /**
     * @param  list<PromotionCode>  $codes
     * @return list<Promotion>
     */
    public function findByCodes(array $codes): array;
}
