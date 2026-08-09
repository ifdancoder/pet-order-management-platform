<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ShippingDestination
{
    public function __construct(
        private string $countryCode,
        private string $postalCode,
    ) {
        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new InvalidArgumentException(
                'Country code must be ISO 3166-1 alpha-2.',
            );
        }

        if (trim($postalCode) === '') {
            throw new InvalidArgumentException('Postal code cannot be blank.');
        }
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }
}
