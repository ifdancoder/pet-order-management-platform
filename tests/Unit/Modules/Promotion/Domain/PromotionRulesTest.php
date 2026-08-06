<?php

declare(strict_types=1);

use Modules\Promotion\Domain\Discount\CompositeDiscount;
use Modules\Promotion\Domain\Discount\FixedDiscount;
use Modules\Promotion\Domain\Discount\PercentageDiscount;
use Modules\Promotion\Domain\Specification\AndSpecification;
use Modules\Promotion\Domain\Specification\CustomerEligibilitySpecification;
use Modules\Promotion\Domain\Specification\DateRangeSpecification;
use Modules\Promotion\Domain\Specification\MinimumOrderAmountSpecification;
use Modules\Promotion\Domain\Specification\ProductEligibilitySpecification;
use Modules\Promotion\Domain\Specification\PromotionContext;
use Modules\Promotion\Domain\ValueObject\Percentage;
use Shared\Domain\ValueObject\Money;

it('requires every composed eligibility rule to pass', function (): void {
    $specification = new AndSpecification([
        new MinimumOrderAmountSpecification(new Money(10_000, 'USD')),
        new DateRangeSpecification(
            new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-09-30T23:59:59+00:00'),
        ),
        new CustomerEligibilitySpecification(['customer-1']),
        new ProductEligibilitySpecification(['PHONE-1']),
    ]);

    $eligible = $specification->isSatisfiedBy(promotionContext());
    $wrongCustomer = $specification->isSatisfiedBy(
        promotionContext(customerId: 'customer-2'),
    );

    expect($eligible)->toBeTrue()
        ->and($wrongCustomer)->toBeFalse();
});

it('treats empty customer and product restrictions as unrestricted', function (): void {
    $specification = new AndSpecification([
        new CustomerEligibilitySpecification([]),
        new ProductEligibilitySpecification([]),
    ]);

    expect($specification->isSatisfiedBy(promotionContext()))->toBeTrue();
});

it('composes percentage and fixed discounts against the remaining subtotal', function (): void {
    $discount = new CompositeDiscount([
        new PercentageDiscount(new Percentage(1_000)),
        new FixedDiscount(new Money(2_000, 'USD')),
    ]);

    $amount = $discount->calculate(new Money(10_000, 'USD'));

    expect($amount->amount())->toBe(3_000)
        ->and($amount->currency())->toBe('USD');
});

it('caps a fixed discount at the remaining subtotal', function (): void {
    $discount = new CompositeDiscount([
        new FixedDiscount(new Money(8_000, 'USD')),
        new FixedDiscount(new Money(8_000, 'USD')),
    ]);

    expect($discount->calculate(new Money(10_000, 'USD'))->amount())->toBe(10_000);
});

function promotionContext(string $customerId = 'customer-1'): PromotionContext
{
    return new PromotionContext(
        customerId: $customerId,
        subtotal: new Money(12_000, 'USD'),
        productSkus: ['PHONE-1'],
        evaluatedAt: new DateTimeImmutable('2026-09-15T12:00:00+00:00'),
    );
}
