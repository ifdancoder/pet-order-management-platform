<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Http\V1\Controller\RequestPaymentController;

Route::post('/{orderId}/payments', RequestPaymentController::class)
    ->whereUuid('orderId')
    ->middleware(['identity.auth', 'throttle:payment-request'])
    ->name('requests.store');
