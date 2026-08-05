<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\V1\Controller\LoginController;
use Modules\Identity\Presentation\Http\V1\Controller\LogoutController;
use Modules\Identity\Presentation\Http\V1\Controller\RefreshAccessTokenController;
use Modules\Identity\Presentation\Http\V1\Controller\RegisterController;

Route::prefix('auth')
    ->name('auth.')
    ->group(function (): void {
        Route::post('/register', RegisterController::class)
            ->middleware('throttle:identity-register')
            ->name('register');

        Route::post('/login', LoginController::class)
            ->middleware('throttle:identity-login')
            ->name('login');

        Route::post('/refresh', RefreshAccessTokenController::class)
            ->middleware('throttle:identity-refresh')
            ->name('refresh');

        Route::post('/logout', LogoutController::class)
            ->middleware('throttle:identity-refresh')
            ->name('logout');
    });
