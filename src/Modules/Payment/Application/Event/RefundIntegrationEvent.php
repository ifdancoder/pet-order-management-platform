<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Event;

use Shared\Application\Event\IIntegrationEvent;

final readonly class RefundIntegrationEvent implements IIntegrationEvent
{
    public function __construct(
        private string $refundId,
        private string $returnId,
        private int $amount,
        private string $currency,
    ) {}

    public function name(): string
    {
        return 'payment.refunded.v1';
    }

    public function aggregateId(): string
    {
        return $this->refundId;
    }

    /** @return array{refund_id: string, return_id: string, amount: int, currency: string} */
    public function payload(): array
    {
        return [
            'refund_id' => $this->refundId,
            'return_id' => $this->returnId,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
