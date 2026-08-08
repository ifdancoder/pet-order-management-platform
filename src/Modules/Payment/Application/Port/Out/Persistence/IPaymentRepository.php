<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Persistence;

use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\PaymentId;

interface IPaymentRepository
{
    public function claim(Payment $payment): bool;

    public function save(Payment $payment): void;

    public function findByIdempotencyKey(IdempotencyKey $key): ?Payment;

    public function findByIdForUpdate(PaymentId $paymentId): ?Payment;

    public function findByProviderPaymentIdForUpdate(
        string $providerPaymentId,
    ): ?Payment;
}
