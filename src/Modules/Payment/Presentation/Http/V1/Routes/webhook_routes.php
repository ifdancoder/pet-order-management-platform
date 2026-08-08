<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Http\V1\Controller\PaymentWebhookController;

Route::post('/webhooks/{provider}', PaymentWebhookController::class)
    ->whereIn('provider', ['stripe', 'paypal'])
    ->middleware('throttle:payment-webhook')
    ->name('process');
