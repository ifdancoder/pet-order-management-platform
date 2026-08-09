<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Service;

use Modules\Shipping\Domain\Contract\IShippingCostStrategy;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Exception\DuplicateShippingStrategy;
use Modules\Shipping\Domain\Exception\ShippingStrategyNotFound;

final class ShippingStrategyResolver
{
    /** @var array<string, IShippingCostStrategy> */
    private array $strategies = [];

    /** @param iterable<IShippingCostStrategy> $strategies */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $method = $strategy->method();

            if (isset($this->strategies[$method->value])) {
                throw DuplicateShippingStrategy::forMethod($method);
            }

            $this->strategies[$method->value] = $strategy;
        }
    }

    public function resolve(ShippingMethod $method): IShippingCostStrategy
    {
        return $this->strategies[$method->value]
            ?? throw ShippingStrategyNotFound::forMethod($method);
    }
}
