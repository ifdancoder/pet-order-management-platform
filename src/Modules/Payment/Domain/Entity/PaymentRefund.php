<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entity;

use InvalidArgumentException;
use Modules\Payment\Domain\Enum\RefundStatus;
use Modules\Payment\Domain\ValueObject\PaymentId;
use Modules\Payment\Domain\ValueObject\RefundId;
use Shared\Domain\ValueObject\Money;

final class PaymentRefund
{
    public function __construct(
        private readonly RefundId $id,
        private readonly string $returnId,
        private readonly PaymentId $paymentId,
        private readonly string $orderId,
        private readonly Money $amount,
        private RefundStatus $status,
        private ?string $providerRefundId = null,
    ) {
        if (trim($returnId) === '' || trim($orderId) === '') {
            throw new InvalidArgumentException('Return and order IDs cannot be blank.');
        }

        if ($amount->amount() === 0) {
            throw new InvalidArgumentException('Refund amount must be positive.');
        }
    }

    public static function pending(
        RefundId $id,
        string $returnId,
        PaymentId $paymentId,
        string $orderId,
        Money $amount,
    ): self {
        return new self(
            id: $id,
            returnId: $returnId,
            paymentId: $paymentId,
            orderId: $orderId,
            amount: $amount,
            status: RefundStatus::Pending,
        );
    }

    public function id(): RefundId
    {
        return $this->id;
    }

    public function returnId(): string
    {
        return $this->returnId;
    }

    public function paymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): RefundStatus
    {
        return $this->status;
    }

    public function providerRefundId(): ?string
    {
        return $this->providerRefundId;
    }
}
