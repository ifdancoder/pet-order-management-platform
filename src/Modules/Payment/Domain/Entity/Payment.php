<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entity;

use InvalidArgumentException;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Domain\Exception\InvalidPaymentStatusTransition;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\OrderId;
use Modules\Payment\Domain\ValueObject\PaymentId;
use Shared\Domain\ValueObject\Money;

final class Payment
{
    public function __construct(
        private readonly PaymentId $id,
        private readonly OrderId $orderId,
        private readonly Money $amount,
        private readonly PaymentProvider $provider,
        private readonly IdempotencyKey $idempotencyKey,
        private readonly string $requestHash,
        private PaymentStatus $status,
        private ?string $providerPaymentId = null,
        private ?string $failureCode = null,
    ) {
        if (! preg_match('/^[a-f0-9]{64}$/', $requestHash)) {
            throw new InvalidArgumentException(
                'Payment request hash must be a SHA-256 digest.',
            );
        }

        if ($amount->amount() === 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }
    }

    public static function pending(
        PaymentId $id,
        OrderId $orderId,
        Money $amount,
        PaymentProvider $provider,
        IdempotencyKey $idempotencyKey,
        string $requestHash,
    ): self {
        return new self(
            id: $id,
            orderId: $orderId,
            amount: $amount,
            provider: $provider,
            idempotencyKey: $idempotencyKey,
            requestHash: $requestHash,
            status: PaymentStatus::Pending,
        );
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function provider(): PaymentProvider
    {
        return $this->provider;
    }

    public function idempotencyKey(): IdempotencyKey
    {
        return $this->idempotencyKey;
    }

    public function requestHash(): string
    {
        return $this->requestHash;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function providerPaymentId(): ?string
    {
        return $this->providerPaymentId;
    }

    public function failureCode(): ?string
    {
        return $this->failureCode;
    }

    public function authorize(string $providerPaymentId): void
    {
        $this->transition(PaymentStatus::Pending, PaymentStatus::Authorized);
        $this->providerPaymentId = $providerPaymentId;
        $this->failureCode = null;
    }

    public function capture(string $providerPaymentId): void
    {
        $this->transition(PaymentStatus::Authorized, PaymentStatus::Captured);
        $this->providerPaymentId = $providerPaymentId;
    }

    public function fail(string $failureCode): void
    {
        if (! in_array(
            $this->status,
            [PaymentStatus::Pending, PaymentStatus::Authorized],
            true,
        )) {
            throw InvalidPaymentStatusTransition::fromTo(
                $this->status,
                PaymentStatus::Failed,
            );
        }

        $this->status = PaymentStatus::Failed;
        $this->failureCode = $failureCode;
    }

    public function refund(): void
    {
        $this->transition(PaymentStatus::Captured, PaymentStatus::Refunded);
    }

    private function transition(
        PaymentStatus $expected,
        PaymentStatus $target,
    ): void {
        if ($this->status !== $expected) {
            throw InvalidPaymentStatusTransition::fromTo(
                $this->status,
                $target,
            );
        }

        $this->status = $target;
    }
}
