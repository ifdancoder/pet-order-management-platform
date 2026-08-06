<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Customer\Application\Exception\CustomerAlreadyExists;
use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Domain\Exception\AddressNotFound;
use Modules\Customer\Domain\Exception\CustomerArchived;
use Modules\Identity\Application\Exception\EmailAlreadyExists;
use Modules\Identity\Application\Exception\InvalidAccessToken;
use Modules\Identity\Application\Exception\InvalidCredentials;
use Modules\Identity\Application\Exception\InvalidRefreshToken;
use Modules\Identity\Application\Exception\UserNotFound;
use Modules\Identity\Domain\Exception\InvalidUserStatusTransition;
use Modules\Identity\Domain\Exception\UserAlreadyActive;
use Modules\Identity\Domain\Exception\UserAlreadySuspended;
use Modules\Identity\Domain\Exception\UserDisabled;
use Modules\Identity\Domain\Exception\UserNotSuspended;
use Modules\Identity\Presentation\Http\V1\Middleware\AuthenticateAccessToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'identity.auth' => AuthenticateAccessToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(
            fn (UserNotFound $exception) => response()->json([
                'error' => [
                    'code' => 'user_not_found',
                    'message' => 'User was not found.',
                ],
            ], 404),
        );

        $exceptions->render(
            fn (EmailAlreadyExists $exception) => response()->json([
                'error' => [
                    'code' => 'email_already_exists',
                    'message' => 'A user with this email already exists.',
                ],
            ], 409),
        );

        $exceptions->render(
            fn (InvalidCredentials $exception) => response()->json([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'The supplied credentials are invalid.',
                ],
            ], 401),
        );

        $exceptions->render(
            fn (InvalidRefreshToken $exception) => response()->json([
                'error' => [
                    'code' => 'invalid_refresh_token',
                    'message' => 'Refresh token is invalid.',
                ],
            ], 401),
        );

        $exceptions->render(
            fn (InvalidAccessToken $exception) => response()->json([
                'error' => [
                    'code' => 'invalid_access_token',
                    'message' => 'Access token is invalid.',
                ],
            ], 401),
        );

        $exceptions->render(
            fn (
                InvalidUserStatusTransition|UserAlreadyActive|UserAlreadySuspended|UserDisabled|UserNotSuspended $exception,
            ) => response()->json([
                'error' => [
                    'code' => 'invalid_user_status_transition',
                    'message' => $exception->getMessage(),
                ],
            ], 409),
        );

        $exceptions->render(
            fn (CustomerNotFound $exception) => response()->json([
                'error' => [
                    'code' => 'customer_not_found',
                    'message' => 'Customer was not found.',
                ],
            ], 404),
        );

        $exceptions->render(
            fn (AddressNotFound $exception) => response()->json([
                'error' => [
                    'code' => 'address_not_found',
                    'message' => 'Address was not found.',
                ],
            ], 404),
        );

        $exceptions->render(
            fn (CustomerAlreadyExists $exception) => response()->json([
                'error' => [
                    'code' => 'customer_already_exists',
                    'message' => 'A customer profile already exists.',
                ],
            ], 409),
        );

        $exceptions->render(
            fn (CustomerArchived $exception) => response()->json([
                'error' => [
                    'code' => 'customer_archived',
                    'message' => $exception->getMessage(),
                ],
            ], 409),
        );
    })->create();
