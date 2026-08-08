<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Webhook\PayPalPaymentWebhookVerifier;

it('verifies and maps a PayPal capture webhook', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://paypal.test/v1/oauth2/token' => Http::response([
            'access_token' => 'paypal-access-token',
        ]),
        'https://paypal.test/v1/notifications/verify-webhook-signature' => Http::response([
            'verification_status' => 'SUCCESS',
        ]),
    ]);
    $payload = json_encode([
        'id' => 'WH-123',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'paypal-capture-1',
            'supplementary_data' => [
                'related_ids' => [
                    'authorization_id' => 'paypal-authorization-1',
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $event = payPalWebhookVerifier()->verify(new PaymentWebhookRequest(
        provider: PaymentProvider::PayPal,
        payload: $payload,
        signature: 'transmission-signature',
        transmissionId: 'transmission-id',
        transmissionTime: '2026-09-28T00:00:00Z',
        certificateUrl: 'https://paypal.test/certificate.pem',
        authAlgorithm: 'SHA256withRSA',
    ));

    expect($event->eventId)->toBe('WH-123')
        ->and($event->lookupProviderPaymentId)->toBe('paypal-authorization-1')
        ->and($event->providerPaymentId)->toBe('paypal-capture-1')
        ->and($event->status)->toBe(PaymentStatus::Captured);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://paypal.test/v1/notifications/verify-webhook-signature'
        && $request['webhook_id'] === 'configured-webhook-id'
        && $request['transmission_sig'] === 'transmission-signature');
});

function payPalWebhookVerifier(): PayPalPaymentWebhookVerifier
{
    return new PayPalPaymentWebhookVerifier(
        http: app(Factory::class),
        clientId: 'paypal-client',
        clientSecret: 'paypal-secret',
        baseUrl: 'https://paypal.test',
        webhookId: 'configured-webhook-id',
    );
}
