<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Provider;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Shipping\Application\Port\In\IShippingRateCalculator;
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
    }

    public function boot(): void
    {
        $this->app->make(HandlerRegistry::class)->register(
            CalculateShippingCostQuery::class,
            CalculateShippingCostHandler::class,
        );
    }
}
