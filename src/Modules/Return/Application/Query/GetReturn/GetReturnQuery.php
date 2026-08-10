<?php

declare(strict_types=1);

namespace Modules\Return\Application\Query\GetReturn;

use Modules\Return\Domain\Entity\ReturnRequest;
use Shared\Application\Bus\Query\IQuery;

/** @implements IQuery<ReturnRequest> */
final readonly class GetReturnQuery implements IQuery
{
    public function __construct(
        public string $returnId,
        public string $identityUserId,
    ) {}
}
