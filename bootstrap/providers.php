<?php

use App\Providers\AppServiceProvider;
use Modules\Customer\Infrastructure\Provider\CustomerServiceProvider;
use Modules\Identity\Infrastructure\Provider\V1\IdentityServiceProvider;
use Modules\Inventory\Infrastructure\Provider\InventoryServiceProvider;
use Modules\Notification\Infrastructure\Provider\NotificationServiceProvider;
use Modules\Order\Infrastructure\Provider\OrderServiceProvider;
use Modules\Payment\Infrastructure\Provider\PaymentServiceProvider;
use Modules\Promotion\Infrastructure\Provider\PromotionServiceProvider;
use Modules\Shipping\Infrastructure\Provider\ShippingServiceProvider;
use Shared\Infrastructure\Provider\BusServiceProvider;
use Shared\Infrastructure\Provider\MessagingServiceProvider;
use Shared\Infrastructure\Provider\TransactionServiceProvider;

return [
    AppServiceProvider::class,
    BusServiceProvider::class,
    TransactionServiceProvider::class,
    MessagingServiceProvider::class,
    IdentityServiceProvider::class,
    CustomerServiceProvider::class,
    InventoryServiceProvider::class,
    PromotionServiceProvider::class,
    OrderServiceProvider::class,
    PaymentServiceProvider::class,
    NotificationServiceProvider::class,
    ShippingServiceProvider::class,
];
