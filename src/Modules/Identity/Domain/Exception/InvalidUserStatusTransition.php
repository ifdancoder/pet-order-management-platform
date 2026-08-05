<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exception;

use DomainException;
use Modules\Identity\Domain\Enum\UserStatus;

final class InvalidUserStatusTransition extends DomainException
{
    public static function fromTo(UserStatus $from, UserStatus $to): self
    {
        return new self(sprintf(
            'User cannot transition from %s to %s.',
            $from->name,
            $to->name,
        ));
    }
}
