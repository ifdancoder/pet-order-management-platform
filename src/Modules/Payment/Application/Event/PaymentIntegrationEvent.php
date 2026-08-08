<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Event;

use InvalidArgumentException;
use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Shared\Application\Event\IIntegrationEvent;

final readonly class PaymentIntegrationEvent implements IIntegrationEvent
{
    private function __construct(
        private string $eventName,
        private string $paymentId,
        private string $orderId,
        private int $amount,
        private string $currency,
        private string $provider,
        private ?string $providerPaymentId,
        private ?string $failureCode,
    ) {}

    public static function fromPayment(Payment $payment): self
    {
        $eventName = match ($payment->status()) {
            PaymentStatus::Authorized => 'payment.authorized.v1',
            PaymentStatus::Captured => 'payment.captured.v1',
            PaymentStatus::Failed => 'payment.failed.v1',
            PaymentStatus::Refunded => 'payment.refunded.v1',
            PaymentStatus::Pending => throw new InvalidArgumentException(
                'A pending payment does not represent an integration event.',
            ),
        };

        return new self(
            eventName: $eventName,
            paymentId: $payment->id()->value(),
            orderId: $payment->orderId()->value(),
            amount: $payment->amount()->amount(),
            currency: $payment->amount()->currency(),
            provider: $payment->provider()->value,
            providerPaymentId: $payment->providerPaymentId(),
            failureCode: $payment->failureCode(),
        );
    }

    public function name(): string
    {
        return $this->eventName;
    }

    public function aggregateId(): string
    {
        return $this->paymentId;
    }

    /**
     * @return array{payment_id: string, order_id: string, amount: int, currency: string, provider: string, provider_payment_id: ?string, failure_code: ?string}
     */
    public function payload(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'provider_payment_id' => $this->providerPaymentId,
            'failure_code' => $this->failureCode,
        ];
    }
}
