<?php

declare(strict_types=1);

namespace Modules\Promotion\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Promotion\Application\Checkout\PromotionCheckout;
use Modules\Promotion\Application\Port\In\IPromotionCheckout;
use Modules\Promotion\Application\Port\Out\Persistence\IPromotionRepository;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentPromotionRepository;

final class PromotionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IPromotionRepository::class, EloquentPromotionRepository::class);
        $this->app->bind(IPromotionCheckout::class, PromotionCheckout::class);
    }
}
