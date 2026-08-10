<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Data;

final readonly class RefundDispatchResult
{
    public function __construct(
        public int $claimed,
        public int $completed,
        public int $retrying,
        public int $failed,
    ) {}
}
