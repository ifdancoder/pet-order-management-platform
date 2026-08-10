<?php

declare(strict_types=1);

namespace Modules\Return\Application\Command\ReceiveReturn;

use Modules\Return\Application\Event\ReturnIntegrationEvent;
use Modules\Return\Application\Exception\ReturnNotFound;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\ValueObject\ReturnId;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class ReceiveReturnHandler
{
    public function __construct(
        private IReturnRepository $returns,
        private ITransactionManager $transaction,
        private IOutboxWriter $outbox,
    ) {}

    public function __invoke(ReceiveReturnCommand $command): ReturnRequest
    {
        return $this->transaction->run(function () use ($command): ReturnRequest {
            $return = $this->returns->findByIdForUpdate(new ReturnId($command->returnId))
                ?? throw ReturnNotFound::withId($command->returnId);
            $return->receive();
            $this->returns->save($return);
            $this->outbox->record(ReturnIntegrationEvent::fromReturn($return));

            return $return;
        });
    }
}
