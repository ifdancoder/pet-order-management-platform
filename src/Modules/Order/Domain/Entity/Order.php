<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entity;

use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Domain\Exception\EmptyOrder;
use Modules\Order\Domain\Exception\InvalidOrderStatusTransition;
use Modules\Order\Domain\Exception\InvalidPromotionDiscount;
use Modules\Order\Domain\Exception\OrderItemAlreadyExists;
use Modules\Order\Domain\Exception\OrderItemNotFound;
use Modules\Order\Domain\ValueObject\CustomerId;
use Modules\Order\Domain\ValueObject\InventoryItemId;
use Modules\Order\Domain\ValueObject\OrderId;
use Shared\Domain\ValueObject\Money;

final class Order
{
    /** @var array<non-empty-string, OrderItem> */
    private array $items = [];

    private Money $discount;

    /** @var list<string> */
    private array $promotionCodes;

    /**
     * @param  list<OrderItem>  $items
     * @param  list<string>  $promotionCodes
     */
    public function __construct(
        private readonly OrderId $id,
        private readonly CustomerId $customerId,
        private readonly string $currency,
        private OrderStatus $status,
        array $items = [],
        ?Money $discount = null,
        array $promotionCodes = [],
    ) {
        Money::zero($currency);

        foreach ($items as $item) {
            $this->addHydratedItem($item);
        }

        $this->discount = $discount ?? Money::zero($currency);
        $this->promotionCodes = [];
        $this->setPromotionDiscount($this->discount, $promotionCodes);
    }

    public static function draft(
        OrderId $id,
        CustomerId $customerId,
        string $currency,
    ): self {
        return new self(
            id: $id,
            customerId: $customerId,
            currency: $currency,
            status: OrderStatus::Draft,
        );
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function total(): Money
    {
        return $this->subtotal()->subtract($this->discount);
    }

    public function subtotal(): Money
    {
        $total = Money::zero($this->currency);

        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal());
        }

        return $total;
    }

    public function discount(): Money
    {
        return $this->discount;
    }

    /** @return list<string> */
    public function promotionCodes(): array
    {
        return $this->promotionCodes;
    }

    /** @param list<string> $promotionCodes */
    public function applyPromotionDiscount(
        Money $discount,
        array $promotionCodes,
    ): void {
        $this->guardStatus(OrderStatus::Draft);
        $this->setPromotionDiscount($discount, $promotionCodes);
    }

    public function addItem(OrderItem $item): void
    {
        $this->guardStatus(OrderStatus::Draft);
        $this->addHydratedItem($item);
    }

    public function removeItem(InventoryItemId $inventoryItemId): void
    {
        $this->guardStatus(OrderStatus::Draft);

        if (! isset($this->items[$inventoryItemId->value()])) {
            throw OrderItemNotFound::withInventoryItemId($inventoryItemId);
        }

        unset($this->items[$inventoryItemId->value()]);
    }

    public function place(): void
    {
        $this->guardStatus(OrderStatus::Draft);

        if ($this->items === []) {
            throw EmptyOrder::cannotBePlaced();
        }

        $this->status = OrderStatus::Placed;
    }

    public function confirm(): void
    {
        $this->transition(OrderStatus::Placed, OrderStatus::Confirmed);
    }

    public function startProcessing(): void
    {
        $this->transition(OrderStatus::Confirmed, OrderStatus::Processing);
    }

    public function markShipped(): void
    {
        $this->transition(OrderStatus::Processing, OrderStatus::Shipped);
    }

    public function complete(): void
    {
        $this->transition(OrderStatus::Shipped, OrderStatus::Completed);
    }

    public function markPaymentFailed(): void
    {
        $this->transition(OrderStatus::Placed, OrderStatus::PaymentFailed);
    }

    public function retryPayment(): void
    {
        $this->transition(OrderStatus::PaymentFailed, OrderStatus::Placed);
    }

    public function cancel(): void
    {
        if (! in_array(
            $this->status,
            [OrderStatus::Draft, OrderStatus::Placed, OrderStatus::Confirmed],
            true,
        )) {
            throw InvalidOrderStatusTransition::fromTo(
                $this->status,
                OrderStatus::Cancelled,
            );
        }

        $this->status = OrderStatus::Cancelled;
    }

    private function addHydratedItem(OrderItem $item): void
    {
        if ($item->unitPrice()->currency() !== $this->currency) {
            $item->unitPrice()->add(Money::zero($this->currency));
        }

        $inventoryItemId = $item->inventoryItemId()->value();

        if (isset($this->items[$inventoryItemId])) {
            throw OrderItemAlreadyExists::withInventoryItemId(
                $item->inventoryItemId(),
            );
        }

        $this->items[$inventoryItemId] = $item;
    }

    /** @param list<string> $promotionCodes */
    private function setPromotionDiscount(
        Money $discount,
        array $promotionCodes,
    ): void {
        if ($discount->amount() > 0 && $promotionCodes === []) {
            throw InvalidPromotionDiscount::missingPromotionCode();
        }

        if ($discount->minimum($this->subtotal())->amount() !== $discount->amount()) {
            throw InvalidPromotionDiscount::exceedsSubtotal();
        }

        $uniqueCodes = [];

        foreach ($promotionCodes as $promotionCode) {
            if (isset($uniqueCodes[$promotionCode])) {
                throw InvalidPromotionDiscount::duplicatePromotionCode(
                    $promotionCode,
                );
            }

            $uniqueCodes[$promotionCode] = true;
        }

        $this->discount = $discount;
        $this->promotionCodes = $promotionCodes;
    }

    private function transition(
        OrderStatus $expected,
        OrderStatus $target,
    ): void {
        $this->guardStatus($expected, $target);
        $this->status = $target;
    }

    private function guardStatus(
        OrderStatus $expected,
        ?OrderStatus $target = null,
    ): void {
        if ($this->status !== $expected) {
            throw InvalidOrderStatusTransition::fromTo(
                $this->status,
                $target ?? $expected,
            );
        }
    }
}
