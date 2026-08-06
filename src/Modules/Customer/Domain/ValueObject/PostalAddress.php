<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\ValueObject;

use InvalidArgumentException;

final readonly class PostalAddress
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
        $this->assertNotBlank($recipientName, 'Recipient name');
        $this->assertNotBlank($line1, 'Address line');
        $this->assertNotBlank($city, 'City');
        $this->assertNotBlank($postalCode, 'Postal code');

        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new InvalidArgumentException('Country code must be ISO 3166-1 alpha-2.');
        }
    }

    public function recipientName(): string
    {
        return $this->recipientName;
    }

    public function line1(): string
    {
        return $this->line1;
    }

    public function line2(): ?string
    {
        return $this->line2;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function region(): ?string
    {
        return $this->region;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    private function assertNotBlank(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($field.' cannot be blank.');
        }
    }
}
