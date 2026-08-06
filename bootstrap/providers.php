<?php

use App\Providers\AppServiceProvider;
use Modules\Identity\Infrastructure\Provider\V1\IdentityServiceProvider;
use Shared\Infrastructure\Provider\BusServiceProvider;
use Shared\Infrastructure\Provider\TransactionServiceProvider;

return [
    AppServiceProvider::class,
    BusServiceProvider::class,
    TransactionServiceProvider::class,
    IdentityServiceProvider::class,
];
