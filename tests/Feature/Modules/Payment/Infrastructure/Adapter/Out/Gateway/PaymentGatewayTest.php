<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Application\Data\GatewayPaymentRequest;
use Modules\Payment\Application\Exception\PaymentGatewayUnavailable;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\PayPalPaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\StripePaymentGateway;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('authorizes Stripe payments with the provider idempotency key', function (): void {
    Http::fake([
        'https://stripe.test/v1/payment_intents' => Http::response([
            'id' => 'pi_123',
            'status' => 'requires_capture',
        ]),
    ]);

    $result = stripeGateway()->authorize(gatewayPaymentRequest());

    expect($result->succeeded)->toBeTrue()
        ->and($result->providerPaymentId)->toBe('pi_123');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://stripe.test/v1/payment_intents'
        && $request->hasHeader('Idempotency-Key', 'payment-request-1')
        && $request['amount'] === 2500
        && $request['payment_method'] === 'pm_123'
        && $request['capture_method'] === 'manual');
});

it('maps Stripe declines and treats provider failures as retryable', function (): void {
    Http::fake([
        'https://stripe.test/v1/payment_intents' => Http::sequence()
            ->push(['error' => ['code' => 'card_declined']], 402)
            ->push([], 503),
    ]);

    $declined = stripeGateway()->authorize(gatewayPaymentRequest());

    expect($declined->succeeded)->toBeFalse()
        ->and($declined->failureCode)->toBe('card_declined')
        ->and(fn () => stripeGateway()->authorize(gatewayPaymentRequest()))
        ->toThrow(PaymentGatewayUnavailable::class);
});

it('authorizes a PayPal order only when its amount and currency match', function (): void {
    Http::fake([
        'https://paypal.test/v1/oauth2/token' => Http::response([
            'access_token' => 'paypal-access-token',
        ]),
        'https://paypal.test/v2/checkout/orders/PAYPAL-ORDER-1/authorize' => Http::response([
            'purchase_units' => [[
                'payments' => [
                    'authorizations' => [[
                        'id' => 'paypal-authorization-1',
                        'amount' => [
                            'value' => '25.00',
                            'currency_code' => 'USD',
                        ],
                    ]],
                ],
            ]],
        ]),
    ]);

    $result = payPalGateway()->authorize(new GatewayPaymentRequest(
        paymentId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f50',
        orderId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51',
        amount: 2500,
        currency: 'USD',
        idempotencyKey: 'payment-request-1',
        paymentMethodReference: 'PAYPAL-ORDER-1',
    ));

    expect($result->succeeded)->toBeTrue()
        ->and($result->providerPaymentId)->toBe('paypal-authorization-1');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://paypal.test/v2/checkout/orders/PAYPAL-ORDER-1/authorize'
        && $request->hasHeader('PayPal-Request-Id', 'payment-request-1')
        && $request->hasHeader('Authorization', 'Bearer paypal-access-token'));
});

it('rejects mismatched PayPal authorization details', function (): void {
    Http::fake([
        'https://paypal.test/v1/oauth2/token' => Http::response([
            'access_token' => 'paypal-access-token',
        ]),
        'https://paypal.test/v2/checkout/orders/PAYPAL-ORDER-1/authorize' => Http::response([
            'purchase_units' => [[
                'payments' => [
                    'authorizations' => [[
                        'id' => 'paypal-authorization-1',
                        'amount' => [
                            'value' => '24.00',
                            'currency_code' => 'USD',
                        ],
                    ]],
                ],
            ]],
        ]),
    ]);

    expect(fn () => payPalGateway()->authorize(new GatewayPaymentRequest(
        paymentId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f50',
        orderId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51',
        amount: 2500,
        currency: 'USD',
        idempotencyKey: 'payment-request-1',
        paymentMethodReference: 'PAYPAL-ORDER-1',
    )))->toThrow(PaymentGatewayUnavailable::class);
});

function gatewayPaymentRequest(): GatewayPaymentRequest
{
    return new GatewayPaymentRequest(
        paymentId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f50',
        orderId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f51',
        amount: 2500,
        currency: 'USD',
        idempotencyKey: 'payment-request-1',
        paymentMethodReference: 'pm_123',
    );
}

function stripeGateway(): StripePaymentGateway
{
    return new StripePaymentGateway(
        http: app(Factory::class),
        secretKey: 'stripe-secret',
        baseUrl: 'https://stripe.test',
    );
}

function payPalGateway(): PayPalPaymentGateway
{
    return new PayPalPaymentGateway(
        http: app(Factory::class),
        clientId: 'paypal-client',
        clientSecret: 'paypal-secret',
        baseUrl: 'https://paypal.test',
    );
}
