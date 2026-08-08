<?php

declare(strict_types=1);

use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Webhook\StripePaymentWebhookVerifier;

it('verifies and maps a Stripe signature', function (): void {
    $payload = json_encode([
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => ['id' => 'pi_123'],
        ],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'webhook-secret');

    $event = (new StripePaymentWebhookVerifier('webhook-secret'))->verify(
        stripeWebhookRequest($payload, "t={$timestamp},v1={$signature}"),
    );

    expect($event->eventId)->toBe('evt_123')
        ->and($event->lookupProviderPaymentId)->toBe('pi_123')
        ->and($event->status)->toBe(PaymentStatus::Captured);
});

it('rejects an invalid Stripe signature', function (): void {
    $payload = '{"id":"evt_123"}';

    (new StripePaymentWebhookVerifier('webhook-secret'))->verify(
        stripeWebhookRequest($payload, 't='.time().',v1=invalid'),
    );
})->throws(InvalidPaymentWebhook::class);

function stripeWebhookRequest(
    string $payload,
    string $signature,
): PaymentWebhookRequest {
    return new PaymentWebhookRequest(
        provider: PaymentProvider::Stripe,
        payload: $payload,
        signature: $signature,
        transmissionId: null,
        transmissionTime: null,
        certificateUrl: null,
        authAlgorithm: null,
    );
}
