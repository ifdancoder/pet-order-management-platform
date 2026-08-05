<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\V1\Controller\GetUserController;

Route::prefix('users')
    ->name('users.')
    ->group(function (): void {
        Route::get('/{userId}', GetUserController::class)
            ->whereUuid('userId')
            ->name('show');
    });
