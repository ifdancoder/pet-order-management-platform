<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exception;

use DomainException;

final class InvalidPromotionDiscount extends DomainException
{
    public static function exceedsSubtotal(): self
    {
        return new self('Promotion discount cannot exceed the order subtotal.');
    }

    public static function missingPromotionCode(): self
    {
        return new self('A positive discount must reference at least one promotion.');
    }

    public static function duplicatePromotionCode(string $code): self
    {
        return new self(sprintf(
            'Promotion "%s" cannot be applied more than once.',
            $code,
        ));
    }
}
