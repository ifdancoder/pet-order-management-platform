<?php

use App\Providers\AppServiceProvider;
use Modules\Customer\Infrastructure\Provider\CustomerServiceProvider;
use Modules\Identity\Infrastructure\Provider\V1\IdentityServiceProvider;
use Modules\Inventory\Infrastructure\Provider\InventoryServiceProvider;
use Modules\Order\Infrastructure\Provider\OrderServiceProvider;
use Modules\Promotion\Infrastructure\Provider\PromotionServiceProvider;
use Shared\Infrastructure\Provider\BusServiceProvider;
use Shared\Infrastructure\Provider\TransactionServiceProvider;

return [
    AppServiceProvider::class,
    BusServiceProvider::class,
    TransactionServiceProvider::class,
    IdentityServiceProvider::class,
    CustomerServiceProvider::class,
    InventoryServiceProvider::class,
    PromotionServiceProvider::class,
    OrderServiceProvider::class,
];
