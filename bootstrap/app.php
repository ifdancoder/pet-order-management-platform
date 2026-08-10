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
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Application\Exception\PaymentGatewayUnavailable;
use Modules\Payment\Application\Exception\PaymentIdempotencyConflict;
use Modules\Payment\Application\Exception\PaymentNotAllowed;
use Modules\Payment\Application\Exception\PaymentNotFound;
use Modules\Return\Application\Exception\ReturnAlreadyExists;
use Modules\Return\Application\Exception\ReturnNotFound;
use Modules\Return\Application\Exception\ReturnOrderNotFound;
use Modules\Return\Domain\Exception\InvalidReturnStatusTransition;
use Modules\Return\Domain\Exception\ReturnNotEligible;

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

        $exceptions->render(
            fn (PaymentNotAllowed $exception) => response()->json([
                'error' => [
                    'code' => 'payment_not_allowed',
                    'message' => 'The order cannot be paid.',
                ],
            ], 404),
        );

        $exceptions->render(
            fn (PaymentIdempotencyConflict $exception) => response()->json([
                'error' => [
                    'code' => 'payment_idempotency_conflict',
                    'message' => 'The idempotency key was already used for another payment request.',
                ],
            ], 409),
        );

        $exceptions->render(
            fn (PaymentGatewayUnavailable $exception) => response()->json([
                'error' => [
                    'code' => 'payment_gateway_unavailable',
                    'message' => 'The payment provider is temporarily unavailable.',
                ],
            ], 503),
        );

        $exceptions->render(
            fn (InvalidPaymentWebhook $exception) => response()->json([
                'error' => [
                    'code' => 'invalid_payment_webhook',
                    'message' => 'Payment webhook is invalid.',
                ],
            ], 400),
        );

        $exceptions->render(
            fn (PaymentNotFound $exception) => response()->json([
                'error' => [
                    'code' => 'payment_not_found',
                    'message' => 'Payment was not found.',
                ],
            ], 404),
        );

        $exceptions->render(
            fn (ReturnNotFound|ReturnOrderNotFound $exception) => response()->json([
                'error' => [
                    'code' => 'return_not_found',
                    'message' => 'Return or eligible order was not found.',
                ],
            ], 404),
        );

        $exceptions->render(
            fn (ReturnAlreadyExists $exception) => response()->json([
                'error' => [
                    'code' => 'return_already_exists',
                    'message' => 'A return already exists for this order.',
                ],
            ], 409),
        );

        $exceptions->render(
            fn (ReturnNotEligible $exception) => response()->json([
                'error' => [
                    'code' => 'return_not_eligible',
                    'message' => $exception->getMessage(),
                ],
            ], 422),
        );

        $exceptions->render(
            fn (InvalidReturnStatusTransition $exception) => response()->json([
                'error' => [
                    'code' => 'invalid_return_status_transition',
                    'message' => $exception->getMessage(),
                ],
            ], 409),
        );
    })->create();
