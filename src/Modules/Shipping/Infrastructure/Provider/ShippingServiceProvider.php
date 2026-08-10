<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Shipping\Application\Booking\ShipmentDispatcher;
use Modules\Shipping\Application\Command\CreateShipment\CreateShipmentCommand;
use Modules\Shipping\Application\Command\CreateShipment\CreateShipmentHandler;
use Modules\Shipping\Application\Messaging\PaymentCapturedShipmentHandler;
use Modules\Shipping\Application\Port\In\IShippingRateCalculator;
use Modules\Shipping\Application\Port\Out\Identity\IShipmentIdGenerator;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Application\Port\Out\Provider\IShippingProvider;
use Modules\Shipping\Application\Query\CalculateShippingCost\CalculateShippingCostHandler;
use Modules\Shipping\Application\Query\CalculateShippingCost\CalculateShippingCostQuery;
use Modules\Shipping\Application\Rate\ShippingRateCalculator;
use Modules\Shipping\Domain\Service\ShippingCostCalculator;
use Modules\Shipping\Domain\Service\ShippingRegionPolicy;
use Modules\Shipping\Domain\Service\ShippingStrategyResolver;
use Modules\Shipping\Domain\Strategy\CourierShippingStrategy;
use Modules\Shipping\Domain\Strategy\ExpressShippingStrategy;
use Modules\Shipping\Domain\Strategy\InternationalShippingStrategy;
use Modules\Shipping\Domain\Strategy\PickupPointShippingStrategy;
use Modules\Shipping\Infrastructure\Adapter\Out\Identity\LaravelShipmentIdGenerator;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentShipmentRepository;
use Modules\Shipping\Infrastructure\Adapter\Out\Provider\FakeShippingProvider;
use Modules\Shipping\Infrastructure\Adapter\Out\Provider\HttpShippingProvider;
use Modules\Shipping\Infrastructure\Queue\BookPendingShipmentsJob;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ShippingRegionPolicy::class,
            function (Application $application): ShippingRegionPolicy {
                $countryCode = $application
                    ->make(ConfigRepository::class)
                    ->get('shipping.domestic_country_code');

                if (! is_string($countryCode) || $countryCode === '') {
                    throw new LogicException(
                        'Shipping domestic country code is invalid.',
                    );
                }

                return new ShippingRegionPolicy($countryCode);
            },
        );
        $this->app->singleton(
            ShippingStrategyResolver::class,
            function (Application $application): ShippingStrategyResolver {
                $regions = $application->make(ShippingRegionPolicy::class);

                return new ShippingStrategyResolver([
                    new CourierShippingStrategy($regions),
                    new ExpressShippingStrategy($regions),
                    new PickupPointShippingStrategy($regions),
                    new InternationalShippingStrategy($regions),
                ]);
            },
        );
        $this->app->singleton(ShippingCostCalculator::class);
        $this->app->bind(
            IShippingRateCalculator::class,
            ShippingRateCalculator::class,
        );
        $this->app->bind(IShipmentIdGenerator::class, LaravelShipmentIdGenerator::class);
        $this->app->bind(IShipmentRepository::class, EloquentShipmentRepository::class);
        $this->app->tag(
            PaymentCapturedShipmentHandler::class,
            IIntegrationMessageHandler::class,
        );
        $this->app->singleton(
            IShippingProvider::class,
            function (Application $application): IShippingProvider {
                $config = $application->make(ConfigRepository::class);
                $driver = $config->get('shipping.provider.driver');

                return match ($driver) {
                    'fake' => new FakeShippingProvider,
                    'http' => $this->httpProvider($application, $config),
                    default => throw new LogicException(
                        'Shipping provider driver is invalid.',
                    ),
                };
            },
        );
        $this->app->bind(
            ShipmentDispatcher::class,
            function (Application $application): ShipmentDispatcher {
                $config = $application->make(ConfigRepository::class);
                $batchSize = $this->positiveInt($config, 'shipping.dispatch.batch_size');
                $claimTimeout = $this->positiveInt($config, 'shipping.dispatch.claim_timeout_seconds');
                $maximumAttempts = $this->positiveInt($config, 'shipping.dispatch.maximum_attempts');
                $initialRetryDelay = $this->positiveInt($config, 'shipping.dispatch.initial_retry_delay_seconds');
                $maximumRetryDelay = $this->positiveInt($config, 'shipping.dispatch.maximum_retry_delay_seconds');

                if ($maximumRetryDelay < $initialRetryDelay) {
                    throw new LogicException('Shipping retry configuration is invalid.');
                }

                return new ShipmentDispatcher(
                    shipments: $application->make(IShipmentRepository::class),
                    provider: $application->make(IShippingProvider::class),
                    batchSize: $batchSize,
                    claimTimeoutSeconds: $claimTimeout,
                    maximumAttempts: $maximumAttempts,
                    initialRetryDelaySeconds: $initialRetryDelay,
                    maximumRetryDelaySeconds: $maximumRetryDelay,
                );
            },
        );
    }

    public function boot(): void
    {
        $this->app->make(HandlerRegistry::class)->register(
            CalculateShippingCostQuery::class,
            CalculateShippingCostHandler::class,
        );
        $this->app->make(HandlerRegistry::class)->register(
            CreateShipmentCommand::class,
            CreateShipmentHandler::class,
        );
        $this->callAfterResolving(
            Schedule::class,
            static function (Schedule $schedule): void {
                $schedule->job(new BookPendingShipmentsJob, 'shipping')
                    ->name('shipment-dispatcher')
                    ->everySecond()
                    ->withoutOverlapping(1)
                    ->onOneServer();
            },
        );
    }

    private function httpProvider(
        Application $application,
        ConfigRepository $config,
    ): HttpShippingProvider {
        $baseUrl = $config->get('shipping.provider.base_url');
        $apiToken = $config->get('shipping.provider.api_token');

        if (! is_string($baseUrl) || $baseUrl === '' || ! is_string($apiToken) || $apiToken === '') {
            throw new LogicException('HTTP shipping provider configuration is invalid.');
        }

        return new HttpShippingProvider(
            http: $application->make(Factory::class),
            baseUrl: $baseUrl,
            apiToken: $apiToken,
            connectTimeoutSeconds: $this->positiveInt($config, 'shipping.provider.connect_timeout_seconds'),
            timeoutSeconds: $this->positiveInt($config, 'shipping.provider.timeout_seconds'),
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
