<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Domain\Exception\InvalidReturnStatusTransition;
use Modules\Return\Domain\ValueObject\ReturnId;
use Modules\Return\Domain\ValueObject\ReturnItem;
use Shared\Domain\ValueObject\Money;

final class ReturnRequest
{
    /** @var array<string, ReturnItem> */
    private array $items = [];

    /** @param list<ReturnItem> $items */
    public function __construct(
        private readonly ReturnId $id,
        private readonly string $orderId,
        private readonly string $customerId,
        private readonly string $reason,
        private readonly string $currency,
        private ReturnStatus $status,
        array $items,
        private readonly DateTimeImmutable $requestedAt,
    ) {
        if (trim($orderId) === '' || trim($customerId) === '') {
            throw new InvalidArgumentException('Order and customer IDs cannot be blank.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Return reason cannot be blank.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('A return must contain at least one item.');
        }

        Money::zero($currency);

        foreach ($items as $item) {
            if ($item->unitPrice()->currency() !== $currency) {
                $item->unitPrice()->add(Money::zero($currency));
            }

            if (isset($this->items[$item->inventoryItemId()])) {
                throw new InvalidArgumentException('Return items must be unique.');
            }

            $this->items[$item->inventoryItemId()] = $item;
        }
    }

    /** @param list<ReturnItem> $items */
    public static function request(
        ReturnId $id,
        string $orderId,
        string $customerId,
        string $reason,
        string $currency,
        array $items,
        DateTimeImmutable $requestedAt,
    ): self {
        return new self(
            id: $id,
            orderId: $orderId,
            customerId: $customerId,
            reason: $reason,
            currency: $currency,
            status: ReturnStatus::Requested,
            items: $items,
            requestedAt: $requestedAt,
        );
    }

    public function approve(): void
    {
        $this->transition(ReturnStatus::Requested, ReturnStatus::Approved);
    }

    public function reject(): void
    {
        $this->transition(ReturnStatus::Requested, ReturnStatus::Rejected);
    }

    public function receive(): void
    {
        $this->transition(ReturnStatus::Approved, ReturnStatus::Received);
    }

    public function markRefunded(): void
    {
        $this->transition(ReturnStatus::Received, ReturnStatus::Refunded);
    }

    public function id(): ReturnId
    {
        return $this->id;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function status(): ReturnStatus
    {
        return $this->status;
    }

    /** @return list<ReturnItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function refundAmount(): Money
    {
        $amount = Money::zero($this->currency);

        foreach ($this->items as $item) {
            $amount = $amount->add($item->refundAmount());
        }

        return $amount;
    }

    private function transition(ReturnStatus $expected, ReturnStatus $target): void
    {
        if ($this->status !== $expected) {
            throw InvalidReturnStatusTransition::fromTo($this->status, $target);
        }

        $this->status = $target;
    }
}
