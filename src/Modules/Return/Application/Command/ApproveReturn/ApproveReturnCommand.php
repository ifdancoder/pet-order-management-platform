<?php

declare(strict_types=1);

namespace Modules\Return\Application\Command\ApproveReturn;

use Modules\Return\Domain\Entity\ReturnRequest;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<ReturnRequest> */
final readonly class ApproveReturnCommand implements ICommand
{
    public function __construct(
        public string $returnId,
    ) {}
}
