<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Webhook;

use LogicException;
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifier;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifierResolver;
use Modules\Payment\Domain\Enum\PaymentProvider;

final class PaymentWebhookVerifierResolver implements IPaymentWebhookVerifierResolver
{
    /** @var array<string, IPaymentWebhookVerifier> */
    private array $verifiers = [];

    /** @param iterable<IPaymentWebhookVerifier> $verifiers */
    public function __construct(iterable $verifiers)
    {
        foreach ($verifiers as $verifier) {
            $provider = $verifier->provider()->value;

            if (isset($this->verifiers[$provider])) {
                throw new LogicException(sprintf(
                    'Payment webhook verifier "%s" was registered more than once.',
                    $provider,
                ));
            }

            $this->verifiers[$provider] = $verifier;
        }
    }

    public function resolve(PaymentProvider $provider): IPaymentWebhookVerifier
    {
        return $this->verifiers[$provider->value]
            ?? throw InvalidPaymentWebhook::create();
    }
}
