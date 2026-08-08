<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Webhook;

use JsonException;
use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Data\VerifiedPaymentWebhook;
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifier;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Throwable;

final readonly class StripePaymentWebhookVerifier implements IPaymentWebhookVerifier
{
    public function __construct(
        private string $signingSecret,
        private int $toleranceSeconds = 300,
    ) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::Stripe;
    }

    public function verify(PaymentWebhookRequest $request): VerifiedPaymentWebhook
    {
        if ($request->provider !== PaymentProvider::Stripe || $request->signature === null) {
            throw InvalidPaymentWebhook::create();
        }

        [$timestamp, $signatures] = $this->signatureParts($request->signature);

        if (abs(time() - $timestamp) > $this->toleranceSeconds) {
            throw InvalidPaymentWebhook::create();
        }

        $expected = hash_hmac(
            'sha256',
            $timestamp.'.'.$request->payload,
            $this->signingSecret,
        );

        $validSignature = false;

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $validSignature = true;
                break;
            }
        }

        if (! $validSignature) {
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

        return $this->mapEvent($payload);
    }

    /** @return array{int, list<string>} */
    private function signatureParts(string $header): array
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key === 't' && is_string($value) && ctype_digit($value)) {
                $timestamp = (int) $value;
            }

            if ($key === 'v1' && is_string($value) && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            throw InvalidPaymentWebhook::create();
        }

        return [$timestamp, $signatures];
    }

    /** @param array<mixed> $payload */
    private function mapEvent(array $payload): VerifiedPaymentWebhook
    {
        try {
            $eventId = $this->stringAt($payload, ['id']);
            $type = $this->stringAt($payload, ['type']);
            $object = $this->arrayAt($payload, ['data', 'object']);

            return match ($type) {
                'payment_intent.amount_capturable_updated' => $this->paymentIntentEvent(
                    $eventId,
                    $object,
                    PaymentStatus::Authorized,
                ),
                'payment_intent.succeeded' => $this->paymentIntentEvent(
                    $eventId,
                    $object,
                    PaymentStatus::Captured,
                ),
                'payment_intent.payment_failed' => $this->paymentIntentEvent(
                    $eventId,
                    $object,
                    PaymentStatus::Failed,
                    $this->optionalStringAt($object, ['last_payment_error', 'code'])
                        ?? 'payment_failed',
                ),
                'charge.refunded' => $this->refundedEvent($eventId, $object),
                default => throw InvalidPaymentWebhook::create(),
            };
        } catch (InvalidPaymentWebhook $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw InvalidPaymentWebhook::create($exception);
        }
    }

    /** @param array<mixed> $object */
    private function paymentIntentEvent(
        string $eventId,
        array $object,
        PaymentStatus $status,
        ?string $failureCode = null,
    ): VerifiedPaymentWebhook {
        $providerPaymentId = $this->stringAt($object, ['id']);

        return new VerifiedPaymentWebhook(
            eventId: $eventId,
            provider: PaymentProvider::Stripe,
            lookupProviderPaymentId: $providerPaymentId,
            providerPaymentId: $providerPaymentId,
            status: $status,
            failureCode: $failureCode,
        );
    }

    /** @param array<mixed> $object */
    private function refundedEvent(
        string $eventId,
        array $object,
    ): VerifiedPaymentWebhook {
        $providerPaymentId = $this->stringAt($object, ['payment_intent']);

        return new VerifiedPaymentWebhook(
            eventId: $eventId,
            provider: PaymentProvider::Stripe,
            lookupProviderPaymentId: $providerPaymentId,
            providerPaymentId: $providerPaymentId,
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
