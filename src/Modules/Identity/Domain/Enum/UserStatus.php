<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Enum;

enum UserStatus: int
{
    case Pending = 0;
    case Active = 1;
    case Suspended = 2;
    case Disabled = 3;
}
