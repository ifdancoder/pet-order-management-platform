<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ShippingAddress
{
    public function __construct(
        private string $recipientName,
        private string $line1,
        private ?string $line2,
        private string $city,
        private ?string $region,
        private string $postalCode,
        private string $countryCode,
    ) {
        foreach ([
            'Recipient name' => $recipientName,
            'Address line' => $line1,
            'City' => $city,
            'Postal code' => $postalCode,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException($field.' cannot be blank.');
            }
        }

        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new InvalidArgumentException(
                'Country code must be ISO 3166-1 alpha-2.',
            );
        }
    }

    /** @return array{recipient_name: string, line1: string, line2: ?string, city: string, region: ?string, postal_code: string, country_code: string} */
    public function toArray(): array
    {
        return [
            'recipient_name' => $this->recipientName,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
        ];
    }
}
