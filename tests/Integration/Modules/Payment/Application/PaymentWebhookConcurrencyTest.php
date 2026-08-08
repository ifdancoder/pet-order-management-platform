<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Application\Command\ProcessPaymentWebhook\ProcessPaymentWebhookCommand;
use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Data\VerifiedPaymentWebhook;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifier;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifierResolver;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;
use Shared\Application\Bus\Command\ICommandBus;

uses(DatabaseMigrations::class);

it('applies concurrent deliveries of one payment webhook once', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    $payment = PaymentModel::factory()->create([
        'provider' => PaymentProvider::Stripe->value,
        'status' => PaymentStatus::Authorized->value,
        'provider_payment_id' => 'pi_concurrent_123',
    ]);
    app()->instance(
        IPaymentWebhookVerifierResolver::class,
        new ConcurrentPaymentWebhookVerifierResolver(new VerifiedPaymentWebhook(
            eventId: 'evt_concurrent_123',
            provider: PaymentProvider::Stripe,
            lookupProviderPaymentId: 'pi_concurrent_123',
            providerPaymentId: 'pi_concurrent_123',
            status: PaymentStatus::Captured,
        )),
    );
    $barrierPath = paymentWebhookConcurrencyFile('payment-webhook-barrier-');
    unlink($barrierPath);
    $resultPaths = [
        paymentWebhookConcurrencyFile('payment-webhook-result-'),
        paymentWebhookConcurrencyFile('payment-webhook-result-'),
    ];
    $processIds = [];

    try {
        foreach ($resultPaths as $resultPath) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork payment webhook process.');
            }

            if ($processId === 0) {
                runPaymentWebhookProcess($barrierPath, $resultPath);
            }

            $processIds[] = $processId;
        }

        touch($barrierPath);

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                throw new RuntimeException('A payment webhook process failed.');
            }
        }

        DB::purge('pgsql');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => PaymentStatus::Captured->value,
        ]);
        $this->assertDatabaseCount('processed_payment_webhooks', 1);
    } finally {
        foreach ([$barrierPath, ...$resultPaths] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
});

function paymentWebhookConcurrencyFile(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create payment webhook concurrency file.');
    }

    return $path;
}

function runPaymentWebhookProcess(
    string $barrierPath,
    string $resultPath,
): never {
    DB::purge('pgsql');
    $deadline = microtime(true) + 5;

    while (! file_exists($barrierPath)) {
        if (microtime(true) >= $deadline) {
            file_put_contents($resultPath, 'error:barrier_timeout');
            exit(1);
        }

        usleep(1_000);
    }

    try {
        app(ICommandBus::class)->dispatch(new ProcessPaymentWebhookCommand(
            new PaymentWebhookRequest(
                provider: PaymentProvider::Stripe,
                payload: '{}',
                signature: 'signature',
                transmissionId: null,
                transmissionTime: null,
                certificateUrl: null,
                authAlgorithm: null,
            ),
        ));
        file_put_contents($resultPath, 'completed');
        exit(0);
    } catch (Throwable $exception) {
        file_put_contents(
            $resultPath,
            'error:'.$exception::class.':'.$exception->getMessage(),
        );
        exit(1);
    }
}

final readonly class ConcurrentPaymentWebhookVerifierResolver implements IPaymentWebhookVerifierResolver
{
    public function __construct(
        private VerifiedPaymentWebhook $event,
    ) {}

    public function resolve(PaymentProvider $provider): IPaymentWebhookVerifier
    {
        return new ConcurrentPaymentWebhookVerifier($this->event);
    }
}

final readonly class ConcurrentPaymentWebhookVerifier implements IPaymentWebhookVerifier
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
