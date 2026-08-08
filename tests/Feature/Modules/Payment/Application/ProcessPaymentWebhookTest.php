<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Payment\Application\Command\ProcessPaymentWebhook\ProcessPaymentWebhookCommand;
use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Data\VerifiedPaymentWebhook;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifier;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifierResolver;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;
use Shared\Application\Bus\Command\ICommandBus;

uses(LazilyRefreshDatabase::class);

it('applies a payment webhook once when the same event is delivered twice', function (): void {
    $payment = PaymentModel::factory()->create([
        'provider' => PaymentProvider::Stripe->value,
        'status' => PaymentStatus::Authorized->value,
        'provider_payment_id' => 'pi_123',
    ]);
    app()->instance(
        IPaymentWebhookVerifierResolver::class,
        new StubPaymentWebhookVerifierResolver(new VerifiedPaymentWebhook(
            eventId: 'evt_123',
            provider: PaymentProvider::Stripe,
            lookupProviderPaymentId: 'pi_123',
            providerPaymentId: 'pi_123',
            status: PaymentStatus::Captured,
        )),
    );
    $command = paymentWebhookCommand();
    $commandBus = app(ICommandBus::class);

    $commandBus->dispatch($command);
    $commandBus->dispatch($command);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->getKey(),
        'status' => PaymentStatus::Captured->value,
    ]);
    $this->assertDatabaseCount('processed_payment_webhooks', 1);
    $this->assertDatabaseHas('outbox_messages', [
        'event_name' => 'payment.captured.v1',
        'aggregate_id' => $payment->getKey(),
    ]);
    $this->assertDatabaseCount('outbox_messages', 1);
});

it('changes the provider payment id when PayPal capture completes', function (): void {
    $payment = PaymentModel::factory()->create([
        'provider' => PaymentProvider::PayPal->value,
        'status' => PaymentStatus::Authorized->value,
        'provider_payment_id' => 'paypal-authorization-1',
    ]);
    app()->instance(
        IPaymentWebhookVerifierResolver::class,
        new StubPaymentWebhookVerifierResolver(new VerifiedPaymentWebhook(
            eventId: 'WH-123',
            provider: PaymentProvider::PayPal,
            lookupProviderPaymentId: 'paypal-authorization-1',
            providerPaymentId: 'paypal-capture-1',
            status: PaymentStatus::Captured,
        )),
    );

    $command = paymentWebhookCommand(PaymentProvider::PayPal);
    $commandBus = app(ICommandBus::class);
    $commandBus->dispatch($command);
    $commandBus->dispatch($command);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->getKey(),
        'status' => PaymentStatus::Captured->value,
        'provider_payment_id' => 'paypal-capture-1',
    ]);
    $this->assertDatabaseCount('processed_payment_webhooks', 1);
    $this->assertDatabaseCount('outbox_messages', 1);
});

it('acknowledges a processed webhook through the HTTP adapter', function (): void {
    PaymentModel::factory()->create([
        'provider' => PaymentProvider::Stripe->value,
        'status' => PaymentStatus::Authorized->value,
        'provider_payment_id' => 'pi_123',
    ]);
    app()->instance(
        IPaymentWebhookVerifierResolver::class,
        new StubPaymentWebhookVerifierResolver(new VerifiedPaymentWebhook(
            eventId: 'evt_http_123',
            provider: PaymentProvider::Stripe,
            lookupProviderPaymentId: 'pi_123',
            providerPaymentId: 'pi_123',
            status: PaymentStatus::Captured,
        )),
    );

    $this->withHeader('Stripe-Signature', 'signature')
        ->postJson(route('payment-webhooks.process', ['provider' => 'stripe']), [])
        ->assertNoContent();

    $this->assertDatabaseHas('payments', [
        'provider_payment_id' => 'pi_123',
        'status' => PaymentStatus::Captured->value,
    ]);
});

function paymentWebhookCommand(
    PaymentProvider $provider = PaymentProvider::Stripe,
): ProcessPaymentWebhookCommand {
    return new ProcessPaymentWebhookCommand(new PaymentWebhookRequest(
        provider: $provider,
        payload: '{}',
        signature: 'signature',
        transmissionId: null,
        transmissionTime: null,
        certificateUrl: null,
        authAlgorithm: null,
    ));
}

final readonly class StubPaymentWebhookVerifierResolver implements IPaymentWebhookVerifierResolver
{
    public function __construct(
        private VerifiedPaymentWebhook $event,
    ) {}

    public function resolve(PaymentProvider $provider): IPaymentWebhookVerifier
    {
        return new StubPaymentWebhookVerifier($this->event);
    }
}

final readonly class StubPaymentWebhookVerifier implements IPaymentWebhookVerifier
{
    public function __construct(
        private VerifiedPaymentWebhook $event,
    ) {}

    public function provider(): PaymentProvider
    {
        return $this->event->provider;
    }

    public function verify(PaymentWebhookRequest $request): VerifiedPaymentWebhook
    {
        return $this->event;
    }
}
