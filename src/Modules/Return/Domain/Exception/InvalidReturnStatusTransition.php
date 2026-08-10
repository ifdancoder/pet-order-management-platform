<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Exception;

use DomainException;
use Modules\Return\Domain\Enum\ReturnStatus;

final class InvalidReturnStatusTransition extends DomainException
{
    public static function fromTo(ReturnStatus $from, ReturnStatus $to): self
    {
        return new self(sprintf(
            'Return cannot transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
