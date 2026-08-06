<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Promotion\Application\Data\PromotionQuoteRequest;
use Modules\Promotion\Application\Exception\PromotionNotApplicable;
use Modules\Promotion\Application\Exception\PromotionNotFound;
use Modules\Promotion\Application\Port\In\IPromotionCheckout;
use Modules\Promotion\Domain\Enum\DiscountType;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionCustomerEligibilityModel;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionModel;
use Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PromotionProductEligibilityModel;

uses(LazilyRefreshDatabase::class);

it('applies eligible promotions in request order', function (): void {
    $percentage = PromotionModel::factory()->create(promotionAttributes([
        'code' => 'SAVE10',
        'discount_type' => DiscountType::Percentage->value,
        'discount_value' => 1_000,
        'minimum_order_amount' => 5_000,
    ]));
    $fixed = PromotionModel::factory()->create(promotionAttributes([
        'code' => 'LESS20',
        'discount_type' => DiscountType::Fixed->value,
        'discount_value' => 2_000,
    ]));
    PromotionCustomerEligibilityModel::query()->create([
        'promotion_id' => $percentage->getKey(),
        'customer_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
    ]);
    PromotionProductEligibilityModel::query()->create([
        'promotion_id' => $fixed->getKey(),
        'sku' => 'PHONE-1',
    ]);

    $quote = app(IPromotionCheckout::class)->quote(promotionQuoteRequest([
        'SAVE10',
        'LESS20',
    ]));

    expect($quote->appliedPromotionCodes)->toBe(['SAVE10', 'LESS20'])
        ->and($quote->discountAmount)->toBe(3_000)
        ->and($quote->currency)->toBe('USD');
});

it('rejects a promotion outside its eligibility rules', function (): void {
    $promotion = PromotionModel::factory()->create(promotionAttributes([
        'code' => 'PRIVATE10',
    ]));
    PromotionCustomerEligibilityModel::query()->create([
        'promotion_id' => $promotion->getKey(),
        'customer_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f99',
    ]);

    $action = fn () => app(IPromotionCheckout::class)->quote(
        promotionQuoteRequest(['PRIVATE10']),
    );

    expect($action)->toThrow(PromotionNotApplicable::class);
});

it('rejects an unknown promotion code', function (): void {
    $action = fn () => app(IPromotionCheckout::class)->quote(
        promotionQuoteRequest(['UNKNOWN']),
    );

    expect($action)->toThrow(PromotionNotFound::class);
});

it('returns a zero discount when no promotion was requested', function (): void {
    $quote = app(IPromotionCheckout::class)->quote(promotionQuoteRequest([]));

    expect($quote->appliedPromotionCodes)->toBe([])
        ->and($quote->discountAmount)->toBe(0);
});

/** @param array<string, mixed> $overrides */
function promotionAttributes(array $overrides = []): array
{
    return $overrides + [
        'enabled' => true,
        'currency' => 'USD',
        'starts_at' => '2026-09-01T00:00:00+00:00',
        'ends_at' => '2026-09-30T23:59:59+00:00',
    ];
}

/** @param list<string> $codes */
function promotionQuoteRequest(array $codes): PromotionQuoteRequest
{
    return new PromotionQuoteRequest(
        promotionCodes: $codes,
        customerId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f41',
        subtotalAmount: 10_000,
        currency: 'USD',
        productSkus: ['PHONE-1'],
        evaluatedAt: new DateTimeImmutable('2026-09-15T12:00:00+00:00'),
    );
}
