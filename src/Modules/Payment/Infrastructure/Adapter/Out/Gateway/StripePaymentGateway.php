<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Modules\Payment\Application\Data\GatewayPaymentRequest;
use Modules\Payment\Application\Data\GatewayPaymentResult;
use Modules\Payment\Application\Exception\PaymentGatewayUnavailable;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGateway;
use Modules\Payment\Domain\Enum\PaymentProvider;

final readonly class StripePaymentGateway implements IPaymentGateway
{
    public function __construct(
        private Factory $http,
        private string $secretKey,
        private string $baseUrl,
    ) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Stripe;
    }

    public function authorize(GatewayPaymentRequest $request): GatewayPaymentResult
    {
        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->withToken($this->secretKey)
                ->withHeader('Idempotency-Key', $request->idempotencyKey)
                ->connectTimeout(3)
                ->timeout(10)
                ->post($this->baseUrl.'/v1/payment_intents', [
                    'amount' => $request->amount,
                    'currency' => strtolower($request->currency),
                    'payment_method' => $request->paymentMethodReference,
                    'capture_method' => 'manual',
                    'confirm' => 'true',
                    'metadata[payment_id]' => $request->paymentId,
                    'metadata[order_id]' => $request->orderId,
                ]);
        } catch (ConnectionException $exception) {
            throw PaymentGatewayUnavailable::forProvider(
                PaymentProvider::Stripe,
                $exception,
            );
        }

        if ($response->serverError() || $response->status() === 429) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::Stripe);
        }

        if ($response->clientError()) {
            return GatewayPaymentResult::failed($this->failureCode($response));
        }

        $providerPaymentId = $response->json('id');
        $status = $response->json('status');

        if (
            ! is_string($providerPaymentId)
            || ! in_array($status, ['requires_capture', 'succeeded'], true)
        ) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::Stripe);
        }

        return GatewayPaymentResult::authorized($providerPaymentId);
    }

    public function capture(
        string $providerPaymentId,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        $response = $this->post(
            sprintf('/v1/payment_intents/%s/capture', rawurlencode($providerPaymentId)),
            $idempotencyKey,
        );

        return $this->successfulResult($response, ['succeeded']);
    }

    public function refund(
        string $providerPaymentId,
        int $amount,
        string $currency,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        $response = $this->post('/v1/refunds', $idempotencyKey, [
            'payment_intent' => $providerPaymentId,
            'amount' => $amount,
        ]);

        return $this->successfulResult($response, ['pending', 'succeeded']);
    }

    private function failureCode(Response $response): string
    {
        $code = $response->json('error.code');

        return is_string($code) && $code !== '' ? $code : 'payment_declined';
    }

    /**
     * @param  array<string, int|string>  $payload
     */
    private function post(
        string $path,
        string $idempotencyKey,
        array $payload = [],
    ): Response {
        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->withToken($this->secretKey)
                ->withHeader('Idempotency-Key', $idempotencyKey)
                ->connectTimeout(3)
                ->timeout(10)
                ->post($this->baseUrl.$path, $payload);
        } catch (ConnectionException $exception) {
            throw PaymentGatewayUnavailable::forProvider(
                PaymentProvider::Stripe,
                $exception,
            );
        }

        if ($response->serverError() || $response->status() === 429) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::Stripe);
        }

        return $response;
    }

    /** @param list<string> $acceptedStatuses */
    private function successfulResult(
        Response $response,
        array $acceptedStatuses,
    ): GatewayPaymentResult {
        if ($response->clientError()) {
            return GatewayPaymentResult::failed($this->failureCode($response));
        }

        $providerPaymentId = $response->json('id');
        $status = $response->json('status');

        if (
            ! is_string($providerPaymentId)
            || ! is_string($status)
            || ! in_array($status, $acceptedStatuses, true)
        ) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::Stripe);
        }

        return GatewayPaymentResult::authorized($providerPaymentId);
    }
}
