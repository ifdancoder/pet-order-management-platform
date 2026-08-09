<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Data;

final readonly class NotificationDispatchResult
{
    public function __construct(
        public int $claimed,
        public int $sent,
        public int $retrying,
        public int $failed,
    ) {}
}
