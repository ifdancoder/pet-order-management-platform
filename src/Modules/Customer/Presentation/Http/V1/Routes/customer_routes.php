<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Presentation\Http\V1\Controller\AddAddressController;
use Modules\Customer\Presentation\Http\V1\Controller\GetCustomerController;
use Modules\Customer\Presentation\Http\V1\Controller\MakeAddressDefaultController;
use Modules\Customer\Presentation\Http\V1\Controller\RegisterCustomerController;
use Modules\Customer\Presentation\Http\V1\Controller\RemoveAddressController;
use Modules\Customer\Presentation\Http\V1\Controller\UpdateAddressController;
use Modules\Customer\Presentation\Http\V1\Controller\UpdateCustomerController;

Route::prefix('profile')
    ->name('profile.')
    ->middleware('identity.auth')
    ->group(function (): void {
        Route::post('/', RegisterCustomerController::class)->name('store');
        Route::get('/', GetCustomerController::class)->name('show');
        Route::patch('/', UpdateCustomerController::class)->name('update');

        Route::post('/addresses', AddAddressController::class)
            ->name('addresses.store');
        Route::put('/addresses/{addressId}', UpdateAddressController::class)
            ->whereUuid('addressId')
            ->name('addresses.update');
        Route::post('/addresses/{addressId}/default', MakeAddressDefaultController::class)
            ->whereUuid('addressId')
            ->name('addresses.default');
        Route::delete('/addresses/{addressId}', RemoveAddressController::class)
            ->whereUuid('addressId')
            ->name('addresses.destroy');
    });
