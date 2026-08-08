<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Provider;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
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
use Modules\Payment\Infrastructure\Adapter\Out\Identity\LaravelPaymentIdGenerator;
use Modules\Payment\Infrastructure\Adapter\Out\Order\OrderPaymentGateway;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentPaymentRepository;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
        $this->app->tag(
            [FakePaymentGateway::class],
            'payment.gateways',
        );
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
    }
}
