<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Persistence;

use Modules\Payment\Application\Data\RefundAttempt;
use Modules\Payment\Domain\Entity\PaymentRefund;

interface IRefundRepository
{
    public function claim(PaymentRefund $refund): bool;

    /** @return list<RefundAttempt> */
    public function claimBatch(int $limit, int $claimTimeoutSeconds): array;

    public function markCompleted(
        string $refundId,
        string $claimToken,
        string $providerRefundId,
    ): void;

    public function release(
        string $refundId,
        string $claimToken,
        string $error,
        int $delaySeconds,
        bool $terminal,
    ): void;
}
