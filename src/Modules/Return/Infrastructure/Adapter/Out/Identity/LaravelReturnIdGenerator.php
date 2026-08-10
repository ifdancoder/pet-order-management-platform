<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Return\Application\Port\Out\Identity\IReturnIdGenerator;
use Modules\Return\Domain\ValueObject\ReturnId;

final readonly class LaravelReturnIdGenerator implements IReturnIdGenerator
{
    public function generate(): ReturnId
    {
        return new ReturnId(Str::uuid7()->toString());
    }
}
