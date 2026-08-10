<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Messaging;

use InvalidArgumentException;
use Modules\Payment\Application\Exception\PaymentNotFound;
use Modules\Payment\Application\Port\Out\Identity\IRefundIdGenerator;
use Modules\Payment\Application\Port\Out\Persistence\IPaymentRepository;
use Modules\Payment\Application\Port\Out\Persistence\IRefundRepository;
use Modules\Payment\Domain\Entity\PaymentRefund;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Domain\ValueObject\Money;

final readonly class ReturnReceivedRefundHandler implements IIntegrationMessageHandler
{
    public function __construct(
        private IPaymentRepository $payments,
        private IRefundRepository $refunds,
        private IRefundIdGenerator $refundIds,
    ) {}

    public function consumerName(): string
    {
        return 'payment-return-received';
    }

    public function messageNames(): array
    {
        return ['return.received.v1'];
    }

    public function handle(IntegrationMessage $message): void
    {
        $returnId = $message->data['return_id'] ?? null;
        $orderId = $message->data['order_id'] ?? null;
        $amount = $message->data['amount'] ?? null;
        $currency = $message->data['currency'] ?? null;

        if (! is_string($returnId) || ! is_string($orderId)
            || ! is_int($amount) || ! is_string($currency)) {
            throw new InvalidArgumentException('Return received message is invalid.');
        }

        $payment = $this->payments->findCapturedByOrderIdForUpdate($orderId)
            ?? throw PaymentNotFound::withId($orderId);

        if ($payment->amount()->currency() !== $currency
            || $amount > $payment->amount()->amount()) {
            throw new InvalidArgumentException('Refund amount does not match the payment.');
        }

        $this->refunds->claim(PaymentRefund::pending(
            id: $this->refundIds->generate(),
            returnId: $returnId,
            paymentId: $payment->id(),
            orderId: $orderId,
            amount: new Money($amount, $currency),
        ));
    }
}
