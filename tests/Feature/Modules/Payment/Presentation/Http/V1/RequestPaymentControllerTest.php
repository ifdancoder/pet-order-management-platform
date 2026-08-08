<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderItemModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);
});

it('creates a payment for the authenticated customer order', function (): void {
    [$user, $order] = paymentHttpRecords();

    $this->withToken('access-token-'.$user->getKey())
        ->withHeader('Idempotency-Key', 'http-payment-1')
        ->postJson(route('payments.requests.store', ['orderId' => $order->getKey()]), [
            'provider' => 'fake',
            'payment_method_reference' => 'fake_method',
        ])
        ->assertCreated()
        ->assertJsonPath('data.order_id', $order->getKey())
        ->assertJsonPath('data.amount', 2500)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.provider', 'fake')
        ->assertJsonPath('data.status', PaymentStatus::Authorized->value);
});

it('returns the same payment for an HTTP retry', function (): void {
    [$user, $order] = paymentHttpRecords();
    $request = fn () => $this->withToken('access-token-'.$user->getKey())
        ->withHeader('Idempotency-Key', 'http-payment-1')
        ->postJson(route('payments.requests.store', ['orderId' => $order->getKey()]), [
            'provider' => 'fake',
            'payment_method_reference' => 'fake_method',
        ]);

    $firstPaymentId = $request()->assertCreated()->json('data.id');

    $request()
        ->assertCreated()
        ->assertJsonPath('data.id', $firstPaymentId);

    $this->assertDatabaseCount('payments', 1);
});

it('requires authentication and an idempotency key', function (): void {
    [, $order] = paymentHttpRecords();
    $route = route('payments.requests.store', ['orderId' => $order->getKey()]);

    $this->postJson($route, [
        'provider' => 'fake',
        'payment_method_reference' => 'fake_method',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'invalid_access_token');

    $this->withToken('access-token-'.UserModel::query()->valueOrFail('id'))
        ->postJson($route, [
            'provider' => 'fake',
            'payment_method_reference' => 'fake_method',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('idempotency_key');
});

it('does not expose another customer order', function (): void {
    [, $order] = paymentHttpRecords();
    $anotherUser = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
    CustomerModel::factory()->create([
        'identity_user_id' => $anotherUser->getKey(),
    ]);

    $this->withToken('access-token-'.$anotherUser->getKey())
        ->withHeader('Idempotency-Key', 'http-payment-1')
        ->postJson(route('payments.requests.store', ['orderId' => $order->getKey()]), [
            'provider' => 'fake',
            'payment_method_reference' => 'fake_method',
        ])
        ->assertNotFound()
        ->assertExactJson([
            'error' => [
                'code' => 'payment_not_allowed',
                'message' => 'The order cannot be paid.',
            ],
        ]);
});

it('rejects unsupported payment providers', function (): void {
    [$user, $order] = paymentHttpRecords();

    $this->withToken('access-token-'.$user->getKey())
        ->withHeader('Idempotency-Key', 'http-payment-1')
        ->postJson(route('payments.requests.store', ['orderId' => $order->getKey()]), [
            'provider' => 'cash',
            'payment_method_reference' => 'fake_method',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('provider');
});

it('reports a configured provider requirement without exposing configuration', function (): void {
    [$user, $order] = paymentHttpRecords();

    $this->withToken('access-token-'.$user->getKey())
        ->withHeader('Idempotency-Key', 'http-payment-1')
        ->postJson(route('payments.requests.store', ['orderId' => $order->getKey()]), [
            'provider' => 'stripe',
            'payment_method_reference' => 'pm_123',
        ])
        ->assertServiceUnavailable()
        ->assertExactJson([
            'error' => [
                'code' => 'payment_gateway_unavailable',
                'message' => 'The payment provider is temporarily unavailable.',
            ],
        ]);
});

/** @return array{UserModel, OrderModel} */
function paymentHttpRecords(): array
{
    $user = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
    $customer = CustomerModel::factory()->create([
        'identity_user_id' => $user->getKey(),
    ]);
    $order = OrderModel::factory()->create([
        'customer_id' => $customer->getKey(),
        'status' => OrderStatus::Placed->value,
        'currency' => 'USD',
        'total_amount' => 2500,
    ]);
    OrderItemModel::factory()->create([
        'order_id' => $order->getKey(),
        'quantity' => 1,
        'unit_price_amount' => 2500,
    ]);

    return [$user, $order];
}
