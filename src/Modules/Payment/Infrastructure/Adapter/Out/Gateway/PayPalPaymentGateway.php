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

final readonly class PayPalPaymentGateway implements IPaymentGateway
{
    public function __construct(
        private Factory $http,
        private string $clientId,
        private string $clientSecret,
        private string $baseUrl,
    ) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::PayPal;
    }

    public function authorize(GatewayPaymentRequest $request): GatewayPaymentResult
    {
        try {
            $accessToken = $this->accessToken();
            $response = $this->http
                ->acceptJson()
                ->withToken($accessToken)
                ->withHeaders([
                    'PayPal-Request-Id' => $request->idempotencyKey,
                    'Prefer' => 'return=representation',
                ])
                ->connectTimeout(3)
                ->timeout(10)
                ->post(sprintf(
                    '%s/v2/checkout/orders/%s/authorize',
                    $this->baseUrl,
                    rawurlencode($request->paymentMethodReference),
                ));
        } catch (ConnectionException $exception) {
            throw PaymentGatewayUnavailable::forProvider(
                PaymentProvider::PayPal,
                $exception,
            );
        }

        if ($response->serverError() || $response->status() === 429) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
        }

        if ($response->clientError()) {
            return GatewayPaymentResult::failed($this->failureCode($response));
        }

        $providerPaymentId = $response->json(
            'purchase_units.0.payments.authorizations.0.id',
        );
        $amount = $response->json(
            'purchase_units.0.payments.authorizations.0.amount.value',
        );
        $currency = $response->json(
            'purchase_units.0.payments.authorizations.0.amount.currency_code',
        );

        if (
            ! is_string($providerPaymentId)
            || ! is_string($amount)
            || ! is_string($currency)
            || $amount !== $this->formatAmount($request->amount, $request->currency)
            || strtoupper($currency) !== $request->currency
        ) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
        }

        return GatewayPaymentResult::authorized($providerPaymentId);
    }

    public function capture(
        string $providerPaymentId,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        $response = $this->post(
            sprintf(
                '/v2/payments/authorizations/%s/capture',
                rawurlencode($providerPaymentId),
            ),
            $idempotencyKey,
        );

        return $this->successfulResult($response, ['COMPLETED', 'PENDING']);
    }

    public function refund(
        string $providerPaymentId,
        int $amount,
        string $currency,
        string $idempotencyKey,
    ): GatewayPaymentResult {
        $response = $this->post(
            sprintf(
                '/v2/payments/captures/%s/refund',
                rawurlencode($providerPaymentId),
            ),
            $idempotencyKey,
            [
                'amount' => [
                    'value' => $this->formatAmount($amount, $currency),
                    'currency_code' => $currency,
                ],
            ],
        );

        return $this->successfulResult($response, ['COMPLETED', 'PENDING']);
    }

    private function accessToken(): string
    {
        $response = $this->http
            ->asForm()
            ->acceptJson()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->connectTimeout(3)
            ->timeout(10)
            ->post($this->baseUrl.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! is_string($accessToken) || $accessToken === '') {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
        }

        return $accessToken;
    }

    private function failureCode(Response $response): string
    {
        $issue = $response->json('details.0.issue');

        return is_string($issue) && $issue !== ''
            ? strtolower($issue)
            : 'payment_declined';
    }

    /** @param array<string, mixed> $payload */
    private function post(
        string $path,
        string $idempotencyKey,
        array $payload = [],
    ): Response {
        try {
            $response = $this->http
                ->acceptJson()
                ->withToken($this->accessToken())
                ->withHeaders([
                    'PayPal-Request-Id' => $idempotencyKey,
                    'Prefer' => 'return=representation',
                ])
                ->connectTimeout(3)
                ->timeout(10)
                ->post($this->baseUrl.$path, $payload);
        } catch (ConnectionException $exception) {
            throw PaymentGatewayUnavailable::forProvider(
                PaymentProvider::PayPal,
                $exception,
            );
        }

        if ($response->serverError() || $response->status() === 429) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
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
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
        }

        return GatewayPaymentResult::authorized($providerPaymentId);
    }

    private function formatAmount(int $minorAmount, string $currency): string
    {
        $exponent = match (strtoupper($currency)) {
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'PYG',
            'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' => 0,
            'BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND' => 3,
            default => 2,
        };

        if ($exponent === 0) {
            return (string) $minorAmount;
        }

        $factor = 10 ** $exponent;

        return sprintf(
            '%d.%0'.$exponent.'d',
            intdiv($minorAmount, $factor),
            $minorAmount % $factor,
        );
    }
}
