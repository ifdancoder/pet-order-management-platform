<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Identity;

use Modules\Payment\Domain\ValueObject\RefundId;

interface IRefundIdGenerator
{
    public function generate(): RefundId;
}
