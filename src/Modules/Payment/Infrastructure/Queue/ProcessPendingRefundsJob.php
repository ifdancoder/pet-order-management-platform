<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Payment\Application\Refund\RefundDispatcher;

final class ProcessPendingRefundsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public int $uniqueFor = 30;

    public function handle(RefundDispatcher $dispatcher): void
    {
        $dispatcher->dispatchPending();
    }

    public function uniqueId(): string
    {
        return 'refund-dispatcher';
    }
}
