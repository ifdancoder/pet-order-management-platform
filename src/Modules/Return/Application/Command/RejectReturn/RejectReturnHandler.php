<?php

declare(strict_types=1);

namespace Modules\Return\Application\Command\RejectReturn;

use Modules\Return\Application\Exception\ReturnNotFound;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\ValueObject\ReturnId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class RejectReturnHandler
{
    public function __construct(
        private IReturnRepository $returns,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RejectReturnCommand $command): ReturnRequest
    {
        return $this->transaction->run(function () use ($command): ReturnRequest {
            $return = $this->returns->findByIdForUpdate(new ReturnId($command->returnId))
                ?? throw ReturnNotFound::withId($command->returnId);
            $return->reject();
            $this->returns->save($return);

            return $return;
        });
    }
}
