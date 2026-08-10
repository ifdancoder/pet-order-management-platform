<?php

declare(strict_types=1);

namespace Modules\Return\Domain\Specification;

final readonly class PurchasedItemsSpecification implements IReturnEligibilitySpecification
{
    public function isSatisfiedBy(ReturnEligibilityContext $context): bool
    {
        foreach ($context->requestedItems as $item) {
            $purchasedQuantity = $context->purchasedQuantities[$item->inventoryItemId()] ?? 0;

            if ($item->quantity() > $purchasedQuantity) {
                return false;
            }
        }

        return true;
    }

    public function rejectionReason(): string
    {
        return 'Requested items or quantities do not belong to the order.';
    }
}
