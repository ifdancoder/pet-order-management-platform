<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\V1\Controller\ActivateUserController;
use Modules\Identity\Presentation\Http\V1\Controller\DisableUserController;
use Modules\Identity\Presentation\Http\V1\Controller\GetUserController;
use Modules\Identity\Presentation\Http\V1\Controller\RestoreUserController;
use Modules\Identity\Presentation\Http\V1\Controller\SuspendUserController;
use Modules\Identity\Presentation\Http\V1\Controller\UpdateUserController;

Route::prefix('users')
    ->name('users.')
    ->middleware('identity.auth')
    ->group(function (): void {
        Route::get('/{userId}', GetUserController::class)
            ->whereUuid('userId')
            ->name('show');

        Route::patch('/{userId}', UpdateUserController::class)
            ->whereUuid('userId')
            ->name('update');

        Route::post('/{userId}/activate', ActivateUserController::class)
            ->whereUuid('userId')
            ->name('activate');

        Route::post('/{userId}/suspend', SuspendUserController::class)
            ->whereUuid('userId')
            ->name('suspend');

        Route::post('/{userId}/restore', RestoreUserController::class)
            ->whereUuid('userId')
            ->name('restore');

        Route::delete('/{userId}', DisableUserController::class)
            ->whereUuid('userId')
            ->name('disable');
    });
