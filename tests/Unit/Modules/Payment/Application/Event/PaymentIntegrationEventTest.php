<?php

declare(strict_types=1);

use Modules\Payment\Application\Event\PaymentIntegrationEvent;
use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\OrderId;
use Modules\Payment\Domain\ValueObject\PaymentId;
use Shared\Domain\ValueObject\Money;

it('creates a versioned authorization event without sensitive request data', function (): void {
    $payment = Payment::pending(
        id: new PaymentId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f50'),
        orderId: new OrderId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f40'),
        amount: new Money(2500, 'USD'),
        provider: PaymentProvider::Stripe,
        idempotencyKey: new IdempotencyKey('secret-client-key'),
        requestHash: hash('sha256', 'request'),
    );
    $payment->authorize('pi_123');

    $event = PaymentIntegrationEvent::fromPayment($payment);

    expect($event->name())->toBe('payment.authorized.v1')
        ->and($event->aggregateId())->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f50')
        ->and($event->payload())->toBe([
            'payment_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f50',
            'order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f40',
            'amount' => 2500,
            'currency' => 'USD',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_123',
            'failure_code' => null,
        ])
        ->and($event->payload())->not->toHaveKey('idempotency_key');
});
