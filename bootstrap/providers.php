<?php

use App\Providers\AppServiceProvider;
use Modules\Identity\Infrastructure\Provider\V1\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
];
