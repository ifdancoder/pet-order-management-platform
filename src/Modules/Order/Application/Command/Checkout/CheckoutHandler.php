<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\Checkout;

use InvalidArgumentException;
use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Checkout\CheckoutRequestHasher;
use Modules\Order\Application\Checkout\CheckoutRulePipeline;
use Modules\Order\Application\Data\CheckoutRecord;
use Modules\Order\Application\Data\CheckoutResult;
use Modules\Order\Application\Exception\CheckoutIdempotencyConflict;
use Modules\Order\Application\Exception\CheckoutInProgress;
use Modules\Order\Application\Port\Out\Checkout\IInventoryCheckoutGateway;
use Modules\Order\Application\Port\Out\Checkout\IShippingCheckoutGateway;
use Modules\Order\Application\Port\Out\Identity\IOrderIdGenerator;
use Modules\Order\Application\Port\Out\Persistence\ICheckoutRepository;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\Entity\Order;
use Modules\Order\Domain\ValueObject\CustomerId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Domain\ValueObject\Money;

final readonly class CheckoutHandler
{
    public function __construct(
        private CheckoutRulePipeline $rules,
        private CheckoutRequestHasher $requestHasher,
        private ICheckoutRepository $checkouts,
        private IOrderRepository $orders,
        private IOrderIdGenerator $orderIds,
        private IInventoryCheckoutGateway $inventory,
        private IShippingCheckoutGateway $shipping,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(CheckoutCommand $command): CheckoutResult
    {
        $this->guardIdempotencyKey($command->idempotencyKey);
        $context = new CheckoutContext(
            customerId: $command->customerId,
            currency: $command->currency,
            items: $command->items,
            promotionCodes: $command->promotionCodes,
            shipping: $command->shipping,
        );
        $requestHash = $this->requestHasher->hash($context);

        return $this->transaction->run(function () use ($command, $context, $requestHash): CheckoutResult {
            if (! $this->checkouts->claim($command->idempotencyKey, $requestHash)) {
                return $this->replay($command->idempotencyKey, $requestHash);
            }

            $this->rules->check($context);

            $order = Order::draft(
                id: $this->orderIds->generate(),
                customerId: new CustomerId($context->customerId),
                currency: $context->currency,
            );

            foreach ($context->items as $item) {
                $order->addItem($item->toOrderItem($context->currency));
            }

            $promotionQuote = $context->promotionQuote();
            $order->applyPromotionDiscount(
                new Money(
                    $promotionQuote->discountAmount,
                    $promotionQuote->currency,
                ),
                $promotionQuote->appliedPromotionCodes,
            );
            $shippingQuote = $context->shippingQuote();

            if ($shippingQuote !== null) {
                $order->applyShippingCost(
                    new Money($shippingQuote->amount, $shippingQuote->currency),
                    $shippingQuote->method,
                );
            }

            $reservationId = $this->inventory->reserve(
                reservationKey: 'checkout:'.hash('sha256', $command->idempotencyKey),
                items: $context->items,
            );
            $order->place();
            $this->orders->save($order);

            if ($context->shipping !== null && $shippingQuote !== null) {
                $this->shipping->createShipment(
                    $order->id()->value(),
                    $context->shipping,
                    $shippingQuote,
                );
            }
            $this->checkouts->complete(
                idempotencyKey: $command->idempotencyKey,
                orderId: $order->id()->value(),
                inventoryReservationId: $reservationId,
            );

            return new CheckoutResult(
                orderId: $order->id()->value(),
                inventoryReservationId: $reservationId,
            );
        });
    }

    private function replay(string $idempotencyKey, string $requestHash): CheckoutResult
    {
        $record = $this->checkouts->findByKey($idempotencyKey)
            ?? throw CheckoutInProgress::forKey($idempotencyKey);

        if (! hash_equals($record->requestHash, $requestHash)) {
            throw CheckoutIdempotencyConflict::forKey($idempotencyKey);
        }

        if (! $record->isCompleted()) {
            throw CheckoutInProgress::forKey($idempotencyKey);
        }

        return $this->resultFrom($record);
    }

    private function resultFrom(CheckoutRecord $record): CheckoutResult
    {
        if ($record->orderId === null || $record->inventoryReservationId === null) {
            throw CheckoutInProgress::forKey($record->idempotencyKey);
        }

        return new CheckoutResult(
            orderId: $record->orderId,
            inventoryReservationId: $record->inventoryReservationId,
        );
    }

    private function guardIdempotencyKey(string $idempotencyKey): void
    {
        $length = mb_strlen($idempotencyKey);

        if ($length < 1 || $length > 128) {
            throw new InvalidArgumentException(
                'Idempotency key must contain between 1 and 128 characters.',
            );
        }
    }
}
