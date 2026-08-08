<?php

declare(strict_types=1);

use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Domain\Exception\InvalidPaymentStatusTransition;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\OrderId;
use Modules\Payment\Domain\ValueObject\PaymentId;
use Shared\Domain\ValueObject\Money;

it('moves through authorization capture and refund', function (): void {
    $payment = pendingPayment();

    $payment->authorize('provider-payment-1');
    expect($payment->status())->toBe(PaymentStatus::Authorized);
    $payment->capture();
    expect($payment->status())->toBe(PaymentStatus::Captured);
    $payment->refund();

    expect($payment->status())->toBe(PaymentStatus::Refunded)
        ->and($payment->providerPaymentId())->toBe('provider-payment-1');
});

it('records a failure before capture', function (): void {
    $payment = pendingPayment();

    $payment->fail('card_declined');

    expect($payment->status())->toBe(PaymentStatus::Failed)
        ->and($payment->failureCode())->toBe('card_declined');
});

it('rejects refund before capture', function (): void {
    pendingPayment()->refund();
})->throws(InvalidPaymentStatusTransition::class);

it('rejects failure after capture', function (): void {
    $payment = pendingPayment();
    $payment->authorize('provider-payment-1');
    $payment->capture();

    $payment->fail('late_failure');
})->throws(InvalidPaymentStatusTransition::class);

function pendingPayment(): Payment
{
    return Payment::pending(
        id: new PaymentId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f50'),
        orderId: new OrderId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f40'),
        amount: new Money(2500, 'USD'),
        provider: PaymentProvider::Fake,
        idempotencyKey: new IdempotencyKey('payment-request-1'),
        requestHash: hash('sha256', 'payment-request'),
    );
}
