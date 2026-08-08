<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Gateway;

use LogicException;
use Modules\Payment\Application\Exception\PaymentGatewayUnavailable;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGateway;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGatewayResolver;
use Modules\Payment\Domain\Enum\PaymentProvider;

final class PaymentGatewayResolver implements IPaymentGatewayResolver
{
    /** @var array<string, IPaymentGateway> */
    private array $gateways = [];

    /** @param iterable<IPaymentGateway> $gateways */
    public function __construct(iterable $gateways)
    {
        foreach ($gateways as $gateway) {
            $provider = $gateway->provider()->value;

            if (isset($this->gateways[$provider])) {
                throw new LogicException(sprintf(
                    'Payment gateway "%s" was registered more than once.',
                    $provider,
                ));
            }

            $this->gateways[$provider] = $gateway;
        }
    }

    public function resolve(PaymentProvider $provider): IPaymentGateway
    {
        return $this->gateways[$provider->value]
            ?? throw PaymentGatewayUnavailable::forProvider($provider);
    }
}
