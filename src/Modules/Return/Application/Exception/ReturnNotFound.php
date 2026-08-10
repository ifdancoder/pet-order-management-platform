<?php

declare(strict_types=1);

namespace Modules\Return\Application\Exception;

use RuntimeException;

final class ReturnNotFound extends RuntimeException
{
    public static function withId(string $returnId): self
    {
        return new self(sprintf('Return %s was not found.', $returnId));
    }
}
