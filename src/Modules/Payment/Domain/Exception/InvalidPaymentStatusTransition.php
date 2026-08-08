<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Exception;

use DomainException;
use Modules\Payment\Domain\Enum\PaymentStatus;

final class InvalidPaymentStatusTransition extends DomainException
{
    public static function fromTo(
        PaymentStatus $from,
        PaymentStatus $to,
    ): self {
        return new self(sprintf(
            'Payment cannot transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
