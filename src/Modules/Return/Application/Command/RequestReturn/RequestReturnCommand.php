<?php

declare(strict_types=1);

namespace Modules\Return\Application\Command\RequestReturn;

use Modules\Return\Domain\Entity\ReturnRequest;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<ReturnRequest> */
final readonly class RequestReturnCommand implements ICommand
{
    /** @param list<array{inventory_item_id: string, quantity: int}> $items */
    public function __construct(
        public string $orderId,
        public string $identityUserId,
        public string $reason,
        public array $items,
    ) {}
}
