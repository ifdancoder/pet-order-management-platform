<?php

declare(strict_types=1);

use Modules\Return\Domain\Exception\ReturnNotEligible;
use Modules\Return\Domain\Service\ReturnEligibilityPolicy;
use Modules\Return\Domain\Specification\AndReturnEligibilitySpecification;
use Modules\Return\Domain\Specification\OrderCompletedSpecification;
use Modules\Return\Domain\Specification\PurchasedItemsSpecification;
use Modules\Return\Domain\Specification\ReturnEligibilityContext;
use Modules\Return\Domain\Specification\WithinReturnWindowSpecification;
use Modules\Return\Domain\ValueObject\ReturnItem;
use Shared\Domain\ValueObject\Money;

function returnEligibilityContext(
    bool $completed = true,
    string $requestedAt = '2026-09-28T10:00:00+00:00',
    int $requestedQuantity = 1,
): ReturnEligibilityContext {
    return new ReturnEligibilityContext(
        orderCompleted: $completed,
        completedAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        requestedAt: new DateTimeImmutable($requestedAt),
        requestedItems: [
            new ReturnItem('inventory-item-1', $requestedQuantity, new Money(1000, 'USD')),
        ],
        purchasedQuantities: ['inventory-item-1' => 2],
    );
}

function returnEligibilityPolicy(): ReturnEligibilityPolicy
{
    return new ReturnEligibilityPolicy(
        new AndReturnEligibilitySpecification([
            new OrderCompletedSpecification,
            new WithinReturnWindowSpecification(30),
            new PurchasedItemsSpecification,
        ]),
    );
}

it('accepts completed orders inside the return window with purchased items', function (): void {
    returnEligibilityPolicy()->assertEligible(returnEligibilityContext());

    expect(true)->toBeTrue();
});

it('rejects orders that are not completed', function (): void {
    expect(fn () => returnEligibilityPolicy()->assertEligible(
        returnEligibilityContext(completed: false),
    ))->toThrow(ReturnNotEligible::class, 'Only completed orders can be returned.');
});

it('rejects requests outside the return window', function (): void {
    expect(fn () => returnEligibilityPolicy()->assertEligible(
        returnEligibilityContext(requestedAt: '2026-10-02T10:00:00+00:00'),
    ))->toThrow(ReturnNotEligible::class, 'The return window has expired.');
});

it('rejects quantities greater than the purchased quantity', function (): void {
    expect(fn () => returnEligibilityPolicy()->assertEligible(
        returnEligibilityContext(requestedQuantity: 3),
    ))->toThrow(
        ReturnNotEligible::class,
        'Requested items or quantities do not belong to the order.',
    );
});
