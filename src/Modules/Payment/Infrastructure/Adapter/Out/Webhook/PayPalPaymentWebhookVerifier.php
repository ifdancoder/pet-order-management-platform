<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Webhook;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use JsonException;
use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Data\VerifiedPaymentWebhook;
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Application\Exception\PaymentGatewayUnavailable;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifier;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;

final readonly class PayPalPaymentWebhookVerifier implements IPaymentWebhookVerifier
{
    public function __construct(
        private Factory $http,
        private string $clientId,
        private string $clientSecret,
        private string $baseUrl,
        private string $webhookId,
    ) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::PayPal;
    }

    public function verify(PaymentWebhookRequest $request): VerifiedPaymentWebhook
    {
        if (
            $request->provider !== PaymentProvider::PayPal
            || $request->signature === null
            || $request->transmissionId === null
            || $request->transmissionTime === null
            || $request->certificateUrl === null
            || $request->authAlgorithm === null
        ) {
            throw InvalidPaymentWebhook::create();
        }

        try {
            $payload = json_decode(
                $request->payload,
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw InvalidPaymentWebhook::create($exception);
        }

        if (! is_array($payload)) {
            throw InvalidPaymentWebhook::create();
        }

        $this->verifySignature($request, $payload);

        return $this->mapEvent($payload);
    }

    /** @param array<mixed> $payload */
    private function verifySignature(
        PaymentWebhookRequest $request,
        array $payload,
    ): void {
        try {
            $response = $this->http
                ->acceptJson()
                ->withToken($this->accessToken())
                ->connectTimeout(3)
                ->timeout(10)
                ->post($this->baseUrl.'/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $request->authAlgorithm,
                    'cert_url' => $request->certificateUrl,
                    'transmission_id' => $request->transmissionId,
                    'transmission_sig' => $request->signature,
                    'transmission_time' => $request->transmissionTime,
                    'webhook_id' => $this->webhookId,
                    'webhook_event' => $payload,
                ]);
        } catch (ConnectionException $exception) {
            throw PaymentGatewayUnavailable::forProvider(
                PaymentProvider::PayPal,
                $exception,
            );
        }

        if ($response->serverError() || $response->status() === 429) {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
        }

        if (
            ! $response->successful()
            || $response->json('verification_status') !== 'SUCCESS'
        ) {
            throw InvalidPaymentWebhook::create();
        }
    }

    private function accessToken(): string
    {
        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->connectTimeout(3)
                ->timeout(10)
                ->post($this->baseUrl.'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException $exception) {
            throw PaymentGatewayUnavailable::forProvider(
                PaymentProvider::PayPal,
                $exception,
            );
        }

        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! is_string($accessToken) || $accessToken === '') {
            throw PaymentGatewayUnavailable::forProvider(PaymentProvider::PayPal);
        }

        return $accessToken;
    }

    /** @param array<mixed> $payload */
    private function mapEvent(array $payload): VerifiedPaymentWebhook
    {
        $eventId = $this->stringAt($payload, ['id']);
        $type = $this->stringAt($payload, ['event_type']);
        $resource = $this->arrayAt($payload, ['resource']);

        return match ($type) {
            'PAYMENT.AUTHORIZATION.CREATED' => $this->authorizationEvent(
                $eventId,
                $resource,
                PaymentStatus::Authorized,
            ),
            'PAYMENT.AUTHORIZATION.DENIED' => $this->authorizationEvent(
                $eventId,
                $resource,
                PaymentStatus::Failed,
                $this->optionalStringAt($resource, ['status_details', 'reason'])
                    ?? 'authorization_denied',
            ),
            'PAYMENT.CAPTURE.COMPLETED' => $this->captureEvent(
                $eventId,
                $resource,
                PaymentStatus::Captured,
            ),
            'PAYMENT.CAPTURE.DENIED' => $this->captureEvent(
                $eventId,
                $resource,
                PaymentStatus::Failed,
                $this->optionalStringAt($resource, ['status_details', 'reason'])
                    ?? 'capture_denied',
            ),
            'PAYMENT.CAPTURE.REFUNDED' => $this->refundedEvent($eventId, $resource),
            default => throw InvalidPaymentWebhook::create(),
        };
    }

    /** @param array<mixed> $resource */
    private function authorizationEvent(
        string $eventId,
        array $resource,
        PaymentStatus $status,
        ?string $failureCode = null,
    ): VerifiedPaymentWebhook {
        $authorizationId = $this->stringAt($resource, ['id']);

        return new VerifiedPaymentWebhook(
            eventId: $eventId,
            provider: PaymentProvider::PayPal,
            lookupProviderPaymentId: $authorizationId,
            providerPaymentId: $authorizationId,
            status: $status,
            failureCode: $failureCode,
        );
    }

    /** @param array<mixed> $resource */
    private function captureEvent(
        string $eventId,
        array $resource,
        PaymentStatus $status,
        ?string $failureCode = null,
    ): VerifiedPaymentWebhook {
        return new VerifiedPaymentWebhook(
            eventId: $eventId,
            provider: PaymentProvider::PayPal,
            lookupProviderPaymentId: $this->stringAt(
                $resource,
                ['supplementary_data', 'related_ids', 'authorization_id'],
            ),
            providerPaymentId: $this->stringAt($resource, ['id']),
            status: $status,
            failureCode: $failureCode,
        );
    }

    /** @param array<mixed> $resource */
    private function refundedEvent(
        string $eventId,
        array $resource,
    ): VerifiedPaymentWebhook {
        $captureId = $this->stringAt(
            $resource,
            ['supplementary_data', 'related_ids', 'capture_id'],
        );

        return new VerifiedPaymentWebhook(
            eventId: $eventId,
            provider: PaymentProvider::PayPal,
            lookupProviderPaymentId: $captureId,
            providerPaymentId: $captureId,
            status: PaymentStatus::Refunded,
        );
    }

    /**
     * @param  array<mixed>  $payload
     * @param  list<string>  $path
     */
    private function stringAt(array $payload, array $path): string
    {
        $value = $this->valueAt($payload, $path);

        if (! is_string($value) || $value === '') {
            throw InvalidPaymentWebhook::create();
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $payload
     * @param  list<string>  $path
     */
    private function optionalStringAt(array $payload, array $path): ?string
    {
        $value = $this->valueAt($payload, $path);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<mixed>  $payload
     * @param  list<string>  $path
     * @return array<mixed>
     */
    private function arrayAt(array $payload, array $path): array
    {
        $value = $this->valueAt($payload, $path);

        if (! is_array($value)) {
            throw InvalidPaymentWebhook::create();
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $payload
     * @param  list<string>  $path
     */
    private function valueAt(array $payload, array $path): mixed
    {
        $value = $payload;

        foreach ($path as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
