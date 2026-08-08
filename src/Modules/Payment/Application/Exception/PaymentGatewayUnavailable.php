<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Exception;

use Modules\Payment\Domain\Enum\PaymentProvider;
use RuntimeException;
use Throwable;

final class PaymentGatewayUnavailable extends RuntimeException
{
    public static function forProvider(
        PaymentProvider $provider,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf('Payment gateway "%s" is unavailable.', $provider->value),
            previous: $previous,
        );
    }
}
