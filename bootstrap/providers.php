<?php

use App\Providers\AppServiceProvider;
use Modules\Identity\Infrastructure\Provider\V1\IdentityServiceProvider;
use Shared\Infrastructure\Provider\BusServiceProvider;

return [
    AppServiceProvider::class,
    BusServiceProvider::class,
    IdentityServiceProvider::class,
];
