<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Checkout;

use Modules\Order\Application\Data\ShippingDetailsData;
use Modules\Order\Application\Data\ShippingQuote;
use Modules\Order\Application\Port\Out\Checkout\IShippingCheckoutGateway;
use Modules\Shipping\Application\Command\CreateShipment\CreateShipmentCommand;
use Modules\Shipping\Application\Data\ShippingQuoteRequest;
use Modules\Shipping\Application\Port\In\IShippingRateCalculator;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class ShippingCheckoutGateway implements IShippingCheckoutGateway
{
    public function __construct(
        private IShippingRateCalculator $rates,
        private ICommandBus $commands,
    ) {}

    public function quote(
        ShippingDetailsData $shipping,
        string $currency,
    ): ShippingQuote {
        $quote = $this->rates->quote(new ShippingQuoteRequest(
            method: ShippingMethod::from($shipping->method),
            countryCode: $shipping->countryCode,
            postalCode: $shipping->postalCode,
            weightGrams: $shipping->weightGrams,
            currency: $currency,
        ));

        return new ShippingQuote(
            method: $quote->method->value,
            amount: $quote->amount,
            currency: $quote->currency,
        );
    }

    public function createShipment(
        string $orderId,
        ShippingDetailsData $shipping,
        ShippingQuote $quote,
    ): void {
        $this->commands->dispatch(new CreateShipmentCommand(
            orderId: $orderId,
            method: ShippingMethod::from($shipping->method),
            recipientName: $shipping->recipientName,
            line1: $shipping->line1,
            line2: $shipping->line2,
            city: $shipping->city,
            region: $shipping->region,
            postalCode: $shipping->postalCode,
            countryCode: $shipping->countryCode,
            weightGrams: $shipping->weightGrams,
            costAmount: $quote->amount,
            currency: $quote->currency,
        ));
    }
}
