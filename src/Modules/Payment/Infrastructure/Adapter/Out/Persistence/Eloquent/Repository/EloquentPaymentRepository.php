<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Modules\Payment\Application\Port\Out\Persistence\IPaymentRepository;
use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\PaymentId;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\PaymentMapper;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;

final readonly class EloquentPaymentRepository implements IPaymentRepository
{
    public function __construct(
        private PaymentMapper $mapper,
    ) {}

    public function claim(Payment $payment): bool
    {
        return PaymentModel::query()->insertOrIgnore(
            $this->mapper->toAttributes($payment),
        ) === 1;
    }

    public function save(Payment $payment): void
    {
        $model = PaymentModel::query()->find($payment->id()->value())
            ?? new PaymentModel;
        $this->mapper->mapToModel($payment, $model);
        $model->save();
    }

    public function findByIdempotencyKey(IdempotencyKey $key): ?Payment
    {
        $model = PaymentModel::query()
            ->where('idempotency_key', $key->value())
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByIdForUpdate(PaymentId $paymentId): ?Payment
    {
        $model = PaymentModel::query()
            ->whereKey($paymentId->value())
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByProviderPaymentIdForUpdate(
        string $providerPaymentId,
    ): ?Payment {
        $model = PaymentModel::query()
            ->where('provider_payment_id', $providerPaymentId)
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
