<?php

declare(strict_types=1);

use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Domain\Exception\InvalidReturnStatusTransition;
use Modules\Return\Domain\ValueObject\ReturnId;
use Modules\Return\Domain\ValueObject\ReturnItem;
use Shared\Domain\ValueObject\Money;

function returnRequest(): ReturnRequest
{
    return ReturnRequest::request(
        id: new ReturnId('0199a480-0000-7000-8000-000000000001'),
        orderId: '0199a480-0000-7000-8000-000000000002',
        customerId: '0199a480-0000-7000-8000-000000000003',
        reason: 'Item is damaged.',
        currency: 'USD',
        items: [
            new ReturnItem(
                inventoryItemId: '0199a480-0000-7000-8000-000000000004',
                quantity: 2,
                unitPrice: new Money(1250, 'USD'),
            ),
        ],
        requestedAt: new DateTimeImmutable('2026-09-28T10:00:00+00:00'),
    );
}

it('moves through the approved received and refunded lifecycle', function (): void {
    $return = returnRequest();

    $return->approve();
    $return->receive();
    $return->markRefunded();

    expect($return->status())->toBe(ReturnStatus::Refunded)
        ->and($return->refundAmount()->amount())->toBe(2500);
});

it('can be rejected only while requested', function (): void {
    $return = returnRequest();

    $return->reject();

    expect($return->status())->toBe(ReturnStatus::Rejected)
        ->and(fn () => $return->approve())
        ->toThrow(InvalidReturnStatusTransition::class);
});

it('rejects receiving an unapproved return', function (): void {
    expect(fn () => returnRequest()->receive())
        ->toThrow(InvalidReturnStatusTransition::class);
});
