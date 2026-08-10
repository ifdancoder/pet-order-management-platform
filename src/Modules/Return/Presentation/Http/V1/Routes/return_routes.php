<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Return\Presentation\Http\V1\Controller\GetReturnController;
use Modules\Return\Presentation\Http\V1\Controller\RequestReturnController;

Route::middleware('identity.auth')->group(function (): void {
    Route::post('/', RequestReturnController::class)
        ->middleware('throttle:return-request')
        ->name('store');
    Route::get('/{returnId}', GetReturnController::class)
        ->whereUuid('returnId')
        ->name('show');
});
