<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\OrderId;
use Modules\Payment\Domain\ValueObject\PaymentId;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;
use Shared\Domain\ValueObject\Money;

final readonly class PaymentMapper
{
    public function toDomain(PaymentModel $model): Payment
    {
        return new Payment(
            id: new PaymentId($model->id),
            orderId: new OrderId($model->order_id),
            amount: new Money($model->amount, $model->currency),
            provider: PaymentProvider::from($model->provider),
            idempotencyKey: new IdempotencyKey($model->idempotency_key),
            requestHash: $model->request_hash,
            status: PaymentStatus::from($model->status),
            providerPaymentId: $model->provider_payment_id,
            failureCode: $model->failure_code,
        );
    }

    public function mapToModel(Payment $payment, PaymentModel $model): void
    {
        $model->id = $payment->id()->value();
        $model->order_id = $payment->orderId()->value();
        $model->amount = $payment->amount()->amount();
        $model->currency = $payment->amount()->currency();
        $model->provider = $payment->provider()->value;
        $model->status = $payment->status()->value;
        $model->idempotency_key = $payment->idempotencyKey()->value();
        $model->request_hash = $payment->requestHash();
        $model->provider_payment_id = $payment->providerPaymentId();
        $model->failure_code = $payment->failureCode();
    }

    /** @return array<string, int|string|null> */
    public function toAttributes(Payment $payment): array
    {
        return [
            'id' => $payment->id()->value(),
            'order_id' => $payment->orderId()->value(),
            'amount' => $payment->amount()->amount(),
            'currency' => $payment->amount()->currency(),
            'provider' => $payment->provider()->value,
            'status' => $payment->status()->value,
            'idempotency_key' => $payment->idempotencyKey()->value(),
            'request_hash' => $payment->requestHash(),
            'provider_payment_id' => $payment->providerPaymentId(),
            'failure_code' => $payment->failureCode(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
