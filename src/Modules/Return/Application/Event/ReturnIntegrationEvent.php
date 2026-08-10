<?php

declare(strict_types=1);

namespace Modules\Return\Application\Event;

use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\Enum\ReturnStatus;
use Shared\Application\Event\IIntegrationEvent;

final readonly class ReturnIntegrationEvent implements IIntegrationEvent
{
    private function __construct(
        private string $eventName,
        private string $returnId,
        private string $orderId,
        private int $amount,
        private string $currency,
    ) {}

    public static function fromReturn(ReturnRequest $return): self
    {
        $eventName = match ($return->status()) {
            ReturnStatus::Received => 'return.received.v1',
            ReturnStatus::Refunded => 'return.refunded.v1',
            default => throw new \InvalidArgumentException(
                'Return status does not represent an integration event.',
            ),
        };

        return new self(
            eventName: $eventName,
            returnId: $return->id()->value(),
            orderId: $return->orderId(),
            amount: $return->refundAmount()->amount(),
            currency: $return->currency(),
        );
    }

    public function name(): string
    {
        return $this->eventName;
    }

    public function aggregateId(): string
    {
        return $this->returnId;
    }

    /** @return array{return_id: string, order_id: string, amount: int, currency: string} */
    public function payload(): array
    {
        return [
            'return_id' => $this->returnId,
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
