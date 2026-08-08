<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Provider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Payment\Application\Command\RequestPayment\RequestPaymentCommand;
use Modules\Payment\Application\Command\RequestPayment\RequestPaymentHandler;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGatewayResolver;
use Modules\Payment\Application\Port\Out\Identity\IPaymentIdGenerator;
use Modules\Payment\Application\Port\Out\Order\IOrderPaymentGateway;
use Modules\Payment\Application\Port\Out\Persistence\IPaymentRepository;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\FakePaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\PaymentGatewayResolver;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\PayPalPaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Gateway\StripePaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Identity\LaravelPaymentIdGenerator;
use Modules\Payment\Infrastructure\Adapter\Out\Order\OrderPaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentPaymentRepository;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app->make(ConfigRepository::class);
        $this->app->bind(IPaymentRepository::class, EloquentPaymentRepository::class);
        $this->app->bind(IPaymentIdGenerator::class, LaravelPaymentIdGenerator::class);
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
    }

    public function boot(): void
    {
        $this->app->make(HandlerRegistry::class)->register(
            RequestPaymentCommand::class,
            RequestPaymentHandler::class,
        );

        RateLimiter::for(
            'payment-request',
            fn (Request $request): Limit => Limit::perMinute(10)->by(
                $request->attributes->getString('identity.user_id').'|'.$request->ip(),
            ),
        );

        Route::middleware('api')
            ->prefix('api/v1/orders')
            ->name('payments.')
            ->group(dirname(__DIR__, 2).'/Presentation/Http/V1/Routes/payment_routes.php');
    }
}
