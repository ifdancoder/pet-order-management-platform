<?php

declare(strict_types=1);

namespace Modules\Return\Application\Messaging;

use InvalidArgumentException;
use Modules\Return\Application\Exception\ReturnNotFound;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Domain\ValueObject\ReturnId;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;

final readonly class PaymentRefundedReturnHandler implements IIntegrationMessageHandler
{
    public function __construct(
        private IReturnRepository $returns,
    ) {}

    public function consumerName(): string
    {
        return 'return-payment-refunded';
    }

    public function messageNames(): array
    {
        return ['payment.refunded.v1'];
    }

    public function handle(IntegrationMessage $message): void
    {
        $returnId = $message->data['return_id'] ?? null;

        if (! is_string($returnId)) {
            throw new InvalidArgumentException('Payment refunded message return_id must be a string.');
        }

        $return = $this->returns->findByIdForUpdate(new ReturnId($returnId))
            ?? throw ReturnNotFound::withId($returnId);

        if ($return->status() === ReturnStatus::Refunded) {
            return;
        }

        $return->markRefunded();
        $this->returns->save($return);
    }
}
