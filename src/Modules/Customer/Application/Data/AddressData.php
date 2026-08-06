<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Data;

use Modules\Customer\Domain\ValueObject\PostalAddress;

final readonly class AddressData
{
    public function __construct(
        public string $recipientName,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $region,
        public string $postalCode,
        public string $countryCode,
    ) {}

    public function toPostalAddress(): PostalAddress
    {
        return new PostalAddress(
            recipientName: $this->recipientName,
            line1: $this->line1,
            line2: $this->line2,
            city: $this->city,
            region: $this->region,
            postalCode: $this->postalCode,
            countryCode: $this->countryCode,
        );
    }
}
