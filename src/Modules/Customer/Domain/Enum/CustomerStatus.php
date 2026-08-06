<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Enum;

enum CustomerStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
