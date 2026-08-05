<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Identity\Application\Exception\EmailAlreadyExists;
use Modules\Identity\Application\Exception\InvalidAccessToken;
use Modules\Identity\Application\Exception\InvalidCredentials;
use Modules\Identity\Application\Exception\InvalidRefreshToken;
use Modules\Identity\Application\Exception\UserNotFound;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
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
    })->create();
