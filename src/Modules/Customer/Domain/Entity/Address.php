<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Entity;

use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\PostalAddress;

final class Address
{
    public function __construct(
        private readonly AddressId $id,
        private PostalAddress $postalAddress,
        private bool $default,
    ) {}

    public function id(): AddressId
    {
        return $this->id;
    }

    public function postalAddress(): PostalAddress
    {
        return $this->postalAddress;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function replaceWith(PostalAddress $postalAddress): void
    {
        $this->postalAddress = $postalAddress;
    }

    public function makeDefault(): void
    {
        $this->default = true;
    }

    public function clearDefault(): void
    {
        $this->default = false;
    }
}
