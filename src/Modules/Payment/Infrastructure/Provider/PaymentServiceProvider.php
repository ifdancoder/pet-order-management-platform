<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Provider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Payment\Application\Command\ProcessPaymentWebhook\ProcessPaymentWebhookCommand;
use Modules\Payment\Application\Command\ProcessPaymentWebhook\ProcessPaymentWebhookHandler;
use Modules\Payment\Application\Command\RequestPayment\RequestPaymentCommand;
use Modules\Payment\Application\Command\RequestPayment\RequestPaymentHandler;
use Modules\Payment\Application\Messaging\ReturnReceivedRefundHandler;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGatewayResolver;
use Modules\Payment\Application\Port\Out\Identity\IPaymentIdGenerator;
use Modules\Payment\Application\Port\Out\Identity\IRefundIdGenerator;
use Modules\Payment\Application\Port\Out\Order\IOrderPaymentGateway;
use Modules\Payment\Application\Port\Out\Persistence\IPaymentRepository;
use Modules\Payment\Application\Port\Out\Persistence\IProcessedPaymentWebhookRepository;
use Modules\Payment\Application\Port\Out\Persistence\IRefundRepository;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifierResolver;
use Modules\Payment\Application\Refund\RefundDispatcher;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\FakePaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\PaymentGatewayResolver;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\PayPalPaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\StripePaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Identity\LaravelPaymentIdGenerator;
use Modules\Payment\Infrastructure\Adapter\Out\Identity\LaravelRefundIdGenerator;
use Modules\Payment\Infrastructure\Adapter\Out\Order\OrderPaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentPaymentRepository;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentRefundRepository;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\LaravelProcessedPaymentWebhookRepository;
use Modules\Payment\Infrastructure\Adapter\Out\Webhook\PaymentWebhookVerifierResolver;
use Modules\Payment\Infrastructure\Adapter\Out\Webhook\PayPalPaymentWebhookVerifier;
use Modules\Payment\Infrastructure\Adapter\Out\Webhook\StripePaymentWebhookVerifier;
use Modules\Payment\Infrastructure\Queue\ProcessPendingRefundsJob;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app->make(ConfigRepository::class);
        $this->app->bind(IPaymentRepository::class, EloquentPaymentRepository::class);
        $this->app->bind(IRefundRepository::class, EloquentRefundRepository::class);
        $this->app->bind(
            IProcessedPaymentWebhookRepository::class,
            LaravelProcessedPaymentWebhookRepository::class,
        );
        $this->app->bind(IPaymentIdGenerator::class, LaravelPaymentIdGenerator::class);
        $this->app->bind(IRefundIdGenerator::class, LaravelRefundIdGenerator::class);
        $this->app->bind(IOrderPaymentGateway::class, OrderPaymentGateway::class);
        $this->app->singleton(
            FakePaymentGateway::class,
            function (Application $application): FakePaymentGateway {
                $decline = $application->make(ConfigRepository::class)
                    ->get('payment.fake.decline');

                if (! is_bool($decline)) {
                    throw new LogicException('Fake payment gateway configuration is invalid.');
                }

                return new FakePaymentGateway($decline);
            },
        );
        $gatewayClasses = [FakePaymentGateway::class];

        $stripeSecretKey = $config->get('payment.stripe.secret_key');
        $stripeBaseUrl = $config->get('payment.stripe.base_url');

        if (
            is_string($stripeSecretKey) && $stripeSecretKey !== ''
            && is_string($stripeBaseUrl) && $stripeBaseUrl !== ''
        ) {
            $this->app->singleton(
                StripePaymentGateway::class,
                fn (Application $application): StripePaymentGateway => new StripePaymentGateway(
                    http: $application->make(HttpFactory::class),
                    secretKey: $stripeSecretKey,
                    baseUrl: rtrim($stripeBaseUrl, '/'),
                ),
            );
            $gatewayClasses[] = StripePaymentGateway::class;
        }

        $payPalClientId = $config->get('payment.paypal.client_id');
        $payPalClientSecret = $config->get('payment.paypal.client_secret');
        $payPalBaseUrl = $config->get('payment.paypal.base_url');

        if (
            is_string($payPalClientId) && $payPalClientId !== ''
            && is_string($payPalClientSecret) && $payPalClientSecret !== ''
            && is_string($payPalBaseUrl) && $payPalBaseUrl !== ''
        ) {
            $this->app->singleton(
                PayPalPaymentGateway::class,
                fn (Application $application): PayPalPaymentGateway => new PayPalPaymentGateway(
                    http: $application->make(HttpFactory::class),
                    clientId: $payPalClientId,
                    clientSecret: $payPalClientSecret,
                    baseUrl: rtrim($payPalBaseUrl, '/'),
                ),
            );
            $gatewayClasses[] = PayPalPaymentGateway::class;
        }

        $this->app->tag($gatewayClasses, 'payment.gateways');
        $this->app->singleton(
            IPaymentGatewayResolver::class,
            fn (): PaymentGatewayResolver => new PaymentGatewayResolver(
                $this->app->tagged('payment.gateways'),
            ),
        );

        $webhookVerifierClasses = [];
        $stripeWebhookSecret = $config->get('payment.stripe.webhook_secret');
        $stripeWebhookTolerance = $config->get(
            'payment.stripe.webhook_tolerance_seconds',
        );

        if (
            is_string($stripeWebhookSecret) && $stripeWebhookSecret !== ''
            && is_int($stripeWebhookTolerance) && $stripeWebhookTolerance > 0
        ) {
            $this->app->singleton(
                StripePaymentWebhookVerifier::class,
                fn (): StripePaymentWebhookVerifier => new StripePaymentWebhookVerifier(
                    signingSecret: $stripeWebhookSecret,
                    toleranceSeconds: $stripeWebhookTolerance,
                ),
            );
            $webhookVerifierClasses[] = StripePaymentWebhookVerifier::class;
        }

        $payPalWebhookId = $config->get('payment.paypal.webhook_id');

        if (
            is_string($payPalClientId) && $payPalClientId !== ''
            && is_string($payPalClientSecret) && $payPalClientSecret !== ''
            && is_string($payPalBaseUrl) && $payPalBaseUrl !== ''
            && is_string($payPalWebhookId) && $payPalWebhookId !== ''
        ) {
            $this->app->singleton(
                PayPalPaymentWebhookVerifier::class,
                fn (Application $application): PayPalPaymentWebhookVerifier => new PayPalPaymentWebhookVerifier(
                    http: $application->make(HttpFactory::class),
                    clientId: $payPalClientId,
                    clientSecret: $payPalClientSecret,
                    baseUrl: rtrim($payPalBaseUrl, '/'),
                    webhookId: $payPalWebhookId,
                ),
            );
            $webhookVerifierClasses[] = PayPalPaymentWebhookVerifier::class;
        }

        $this->app->tag($webhookVerifierClasses, 'payment.webhook-verifiers');
        $this->app->singleton(
            IPaymentWebhookVerifierResolver::class,
            fn (): PaymentWebhookVerifierResolver => new PaymentWebhookVerifierResolver(
                $this->app->tagged('payment.webhook-verifiers'),
            ),
        );
        $this->app->tag(ReturnReceivedRefundHandler::class, IIntegrationMessageHandler::class);
        $this->app->bind(RefundDispatcher::class, function (Application $application): RefundDispatcher {
            $config = $application->make(ConfigRepository::class);

            return new RefundDispatcher(
                refunds: $application->make(IRefundRepository::class),
                gateways: $application->make(IPaymentGatewayResolver::class),
                transaction: $application->make(ITransactionManager::class),
                outbox: $application->make(IOutboxWriter::class),
                batchSize: $this->positiveInt($config, 'payment.refund.batch_size'),
                claimTimeoutSeconds: $this->positiveInt($config, 'payment.refund.claim_timeout_seconds'),
                maximumAttempts: $this->positiveInt($config, 'payment.refund.maximum_attempts'),
                initialRetryDelaySeconds: $this->positiveInt($config, 'payment.refund.initial_retry_delay_seconds'),
                maximumRetryDelaySeconds: $this->positiveInt($config, 'payment.refund.maximum_retry_delay_seconds'),
            );
        });
    }

    public function boot(): void
    {
        $handlers = $this->app->make(HandlerRegistry::class);
        $handlers->register(RequestPaymentCommand::class, RequestPaymentHandler::class);
        $handlers->register(
            ProcessPaymentWebhookCommand::class,
            ProcessPaymentWebhookHandler::class,
        );

        RateLimiter::for(
            'payment-request',
            fn (Request $request): Limit => Limit::perMinute(10)->by(
                $request->attributes->getString('identity.user_id').'|'.$request->ip(),
            ),
        );

        RateLimiter::for(
            'payment-webhook',
            fn (Request $request): Limit => Limit::perMinute(120)->by(
                $request->route('provider').'|'.$request->ip(),
            ),
        );

        Route::middleware('api')
            ->prefix('api/v1/orders')
            ->name('payments.')
            ->group(dirname(__DIR__, 2).'/Presentation/Http/V1/Routes/payment_routes.php');

        Route::middleware('api')
            ->prefix('api/v1/payments')
            ->name('payment-webhooks.')
            ->group(dirname(__DIR__, 2).'/Presentation/Http/V1/Routes/webhook_routes.php');

        $this->callAfterResolving(
            Schedule::class,
            static function (Schedule $schedule): void {
                $schedule->job(new ProcessPendingRefundsJob, 'refunds')
                    ->name('refund-dispatcher')
                    ->everySecond()
                    ->withoutOverlapping(1)
                    ->onOneServer();
            },
        );
    }

    private function positiveInt(ConfigRepository $config, string $key): int
    {
        $value = $config->get($key);

        if (! is_int($value) || $value < 1) {
            throw new LogicException(sprintf('Configuration "%s" must be a positive integer.', $key));
        }

        return $value;
    }
}
