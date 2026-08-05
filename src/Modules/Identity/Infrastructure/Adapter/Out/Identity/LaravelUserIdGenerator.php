<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Identity;

use Illuminate\Support\Str;
use Modules\Identity\Application\Port\Out\Identity\IUserIdGenerator;
use Modules\Identity\Domain\ValueObject\UserId;

final class LaravelUserIdGenerator implements IUserIdGenerator
{
    public function generate(): UserId
    {
        return new UserId(
            Str::uuid7()->toString(),
        );
    }
}
