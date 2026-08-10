<?php

declare(strict_types=1);

namespace Modules\Return\Application\Command\RequestReturn;

use Modules\Return\Application\Data\ReturnableOrderItem;
use Modules\Return\Application\Exception\ReturnAlreadyExists;
use Modules\Return\Application\Exception\ReturnOrderNotFound;
use Modules\Return\Application\Port\Out\Identity\IReturnIdGenerator;
use Modules\Return\Application\Port\Out\Order\IReturnOrderGateway;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Application\Port\Out\Time\IReturnClock;
use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\Service\ReturnEligibilityPolicy;
use Modules\Return\Domain\Specification\ReturnEligibilityContext;
use Modules\Return\Domain\ValueObject\ReturnItem;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Domain\ValueObject\Money;

final readonly class RequestReturnHandler
{
    public function __construct(
        private IReturnOrderGateway $orders,
        private IReturnRepository $returns,
        private IReturnIdGenerator $returnIds,
        private IReturnClock $clock,
        private ReturnEligibilityPolicy $eligibility,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RequestReturnCommand $command): ReturnRequest
    {
        $order = $this->orders->findForIdentity(
            $command->orderId,
            $command->identityUserId,
        ) ?? throw ReturnOrderNotFound::withId($command->orderId);
        $requestedItems = [];
        $purchasedQuantities = [];

        foreach ($order->items as $item) {
            $purchasedQuantities[$item->inventoryItemId] = $item->quantity;
        }

        foreach ($command->items as $item) {
            $orderItem = $this->findItem($order->items, $item['inventory_item_id']);
            $unitPriceAmount = $orderItem === null ? 0 : $orderItem->unitPriceAmount;
            $requestedItems[] = new ReturnItem(
                inventoryItemId: $item['inventory_item_id'],
                quantity: $item['quantity'],
                unitPrice: new Money(
                    $unitPriceAmount,
                    $order->currency,
                ),
            );
        }

        $now = $this->clock->now();
        $this->eligibility->assertEligible(new ReturnEligibilityContext(
            orderCompleted: $order->completed,
            completedAt: $order->completedAt,
            requestedAt: $now,
            requestedItems: $requestedItems,
            purchasedQuantities: $purchasedQuantities,
        ));
        $return = ReturnRequest::request(
            id: $this->returnIds->generate(),
            orderId: $order->orderId,
            customerId: $order->customerId,
            reason: $command->reason,
            currency: $order->currency,
            items: $requestedItems,
            requestedAt: $now,
        );

        return $this->transaction->run(function () use ($return): ReturnRequest {
            if (! $this->returns->claim($return)) {
                throw ReturnAlreadyExists::forOrder($return->orderId());
            }

            return $return;
        });
    }

    /**
     * @param  list<ReturnableOrderItem>  $items
     */
    private function findItem(array $items, string $inventoryItemId): ?ReturnableOrderItem
    {
        foreach ($items as $item) {
            if ($item->inventoryItemId === $inventoryItemId) {
                return $item;
            }
        }

        return null;
    }
}
