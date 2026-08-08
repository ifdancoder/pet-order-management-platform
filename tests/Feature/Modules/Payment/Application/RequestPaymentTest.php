<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderItemModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Modules\Payment\Application\Command\RequestPayment\RequestPaymentCommand;
use Modules\Payment\Application\Data\GatewayPaymentRequest;
use Modules\Payment\Application\Data\GatewayPaymentResult;
use Modules\Payment\Application\Exception\PaymentIdempotencyConflict;
use Modules\Payment\Application\Exception\PaymentNotAllowed;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGateway;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGatewayResolver;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Shared\Application\Bus\Command\ICommandBus;

uses(LazilyRefreshDatabase::class);

it('authorizes the payable order through the selected gateway', function (): void {
    [$user, $order] = paymentRecords();

    $payment = app(ICommandBus::class)->dispatch(paymentCommand(
        orderId: $order->getKey(),
        identityUserId: $user->getKey(),
    ));

    expect($payment->status())->toBe(PaymentStatus::Authorized)
        ->and($payment->amount()->amount())->toBe(2500)
        ->and($payment->providerPaymentId())->toStartWith('fake_');
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id()->value(),
        'order_id' => $order->getKey(),
        'status' => PaymentStatus::Authorized->value,
        'amount' => 2500,
        'currency' => 'USD',
    ]);
});

it('returns the same payment when a request is retried', function (): void {
    [$user, $order] = paymentRecords();
    $command = paymentCommand(
        orderId: $order->getKey(),
        identityUserId: $user->getKey(),
    );
    $commandBus = app(ICommandBus::class);

    $first = $commandBus->dispatch($command);
    $second = $commandBus->dispatch($command);

    expect($second->id()->value())->toBe($first->id()->value())
        ->and($second->providerPaymentId())->toBe($first->providerPaymentId());
    $this->assertDatabaseCount('payments', 1);
});

it('rejects reuse of an idempotency key for another provider', function (): void {
    [$user, $order] = paymentRecords();
    $commandBus = app(ICommandBus::class);
    $commandBus->dispatch(paymentCommand(
        orderId: $order->getKey(),
        identityUserId: $user->getKey(),
    ));

    $action = fn () => $commandBus->dispatch(new RequestPaymentCommand(
        orderId: $order->getKey(),
        identityUserId: $user->getKey(),
        provider: PaymentProvider::Stripe,
        idempotencyKey: 'payment-request-1',
        paymentMethodReference: 'pm_another',
    ));

    expect($action)->toThrow(PaymentIdempotencyConflict::class);
    $this->assertDatabaseCount('payments', 1);
});

it('records a declined authorization', function (): void {
    config()->set('payment.fake.decline', true);
    [$user, $order] = paymentRecords();

    $payment = app(ICommandBus::class)->dispatch(paymentCommand(
        orderId: $order->getKey(),
        identityUserId: $user->getKey(),
    ));

    expect($payment->status())->toBe(PaymentStatus::Failed)
        ->and($payment->failureCode())->toBe('payment_declined');
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id()->value(),
        'status' => PaymentStatus::Failed->value,
        'failure_code' => 'payment_declined',
    ]);
});

it('does not keep a database transaction open during gateway authorization', function (): void {
    [$user, $order] = paymentRecords();
    $testTransactionLevel = DB::transactionLevel();
    $gateway = new TransactionObservingPaymentGateway;
    app()->instance(
        IPaymentGatewayResolver::class,
        new TransactionObservingPaymentGatewayResolver($gateway),
    );

    app(ICommandBus::class)->dispatch(paymentCommand(
        orderId: $order->getKey(),
        identityUserId: $user->getKey(),
    ));

    expect($gateway->transactionLevel)->toBe($testTransactionLevel);
});

it('does not reveal or pay an order owned by another customer', function (): void {
    [$user, $order] = paymentRecords();
    $anotherUser = UserModel::factory()->create();
    CustomerModel::factory()->create([
        'identity_user_id' => $anotherUser->getKey(),
    ]);

    $action = fn () => app(ICommandBus::class)->dispatch(paymentCommand(
        orderId: $order->getKey(),
        identityUserId: $anotherUser->getKey(),
    ));

    expect($action)->toThrow(PaymentNotAllowed::class);
    $this->assertDatabaseCount('payments', 0);
});

/** @return array{UserModel, OrderModel} */
function paymentRecords(): array
{
    $user = UserModel::factory()->create();
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

function paymentCommand(
    string $orderId,
    string $identityUserId,
): RequestPaymentCommand {
    return new RequestPaymentCommand(
        orderId: $orderId,
        identityUserId: $identityUserId,
        provider: PaymentProvider::Fake,
        idempotencyKey: 'payment-request-1',
        paymentMethodReference: 'fake_method',
    );
}

final class TransactionObservingPaymentGateway implements IPaymentGateway
{
    public ?int $transactionLevel = null;

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Fake;
    }

    public function authorize(GatewayPaymentRequest $request): GatewayPaymentResult
    {
        $this->transactionLevel = DB::transactionLevel();

        return GatewayPaymentResult::authorized('outside_transaction');
    }

    public function capture(
        string $providerPaymentId,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        return GatewayPaymentResult::authorized($providerPaymentId);
    }

    public function refund(
        string $providerPaymentId,
        int $amount,
        string $currency,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        return GatewayPaymentResult::authorized($providerPaymentId);
    }
}

final readonly class TransactionObservingPaymentGatewayResolver implements IPaymentGatewayResolver
{
    public function __construct(
        private IPaymentGateway $gateway,
    ) {}

    public function resolve(PaymentProvider $provider): IPaymentGateway
    {
        return $this->gateway;
    }
}
