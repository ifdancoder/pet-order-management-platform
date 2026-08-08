<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Command\RequestPayment;

use JsonException;
use Modules\Payment\Application\Data\PayableOrder;
use Modules\Payment\Domain\Enum\PaymentProvider;

final readonly class PaymentRequestHasher
{
    /** @throws JsonException */
    public function hash(
        PayableOrder $order,
        PaymentProvider $provider,
        string $paymentMethodReference,
    ): string {
        return hash('sha256', json_encode([
            'amount' => $order->amount,
            'currency' => $order->currency,
            'order_id' => $order->orderId,
            'provider' => $provider->value,
            'payment_method_reference' => $paymentMethodReference,
        ], JSON_THROW_ON_ERROR));
    }
}
