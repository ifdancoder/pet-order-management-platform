<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Exception;

use RuntimeException;

final class PromotionNotApplicable extends RuntimeException
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('Promotion "%s" is not applicable.', $code));
    }
}
