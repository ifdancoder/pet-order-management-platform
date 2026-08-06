<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout;

use Modules\Order\Application\Checkout\Rule\ICheckoutRule;

final readonly class CheckoutRulePipeline
{
    /** @param iterable<ICheckoutRule> $rules */
    public function __construct(
        private iterable $rules,
    ) {}

    public function check(CheckoutContext $context): void
    {
        foreach ($this->rules as $rule) {
            $rule->check($context);
        }
    }
}
