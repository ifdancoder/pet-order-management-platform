<?php

declare(strict_types=1);

namespace Modules\Return\Application\Command\ReceiveReturn;

use Modules\Return\Domain\Entity\ReturnRequest;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<ReturnRequest> */
final readonly class ReceiveReturnCommand implements ICommand
{
    public function __construct(
        public string $returnId,
    ) {}
}
