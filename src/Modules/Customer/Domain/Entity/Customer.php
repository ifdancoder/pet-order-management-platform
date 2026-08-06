<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Entity;

use Modules\Customer\Domain\Enum\CustomerStatus;
use Modules\Customer\Domain\Exception\AddressAlreadyExists;
use Modules\Customer\Domain\Exception\AddressNotFound;
use Modules\Customer\Domain\Exception\CustomerArchived;
use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Modules\Customer\Domain\ValueObject\PhoneNumber;
use Modules\Customer\Domain\ValueObject\PostalAddress;

final class Customer
{
    /** @var array<non-empty-string, Address> */
    private array $addresses = [];

    /**
     * @param  list<Address>  $addresses
     */
    public function __construct(
        private readonly CustomerId $id,
        private readonly IdentityUserId $identityUserId,
        private CustomerName $name,
        private ?PhoneNumber $phoneNumber,
        private CustomerStatus $status,
        array $addresses = [],
    ) {
        foreach ($addresses as $address) {
            $this->addHydratedAddress($address);
        }
    }

    public static function register(
        CustomerId $id,
        IdentityUserId $identityUserId,
        CustomerName $name,
        ?PhoneNumber $phoneNumber,
    ): self {
        return new self(
            id: $id,
            identityUserId: $identityUserId,
            name: $name,
            phoneNumber: $phoneNumber,
            status: CustomerStatus::Active,
        );
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function identityUserId(): IdentityUserId
    {
        return $this->identityUserId;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function phoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function status(): CustomerStatus
    {
        return $this->status;
    }

    /** @return list<Address> */
    public function addresses(): array
    {
        return array_values($this->addresses);
    }

    public function updateProfile(
        CustomerName $name,
        ?PhoneNumber $phoneNumber,
    ): void {
        $this->guardActive();
        $this->name = $name;
        $this->phoneNumber = $phoneNumber;
    }

    public function addAddress(
        AddressId $addressId,
        PostalAddress $postalAddress,
        bool $makeDefault = false,
    ): void {
        $this->guardActive();

        if (isset($this->addresses[$addressId->value()])) {
            throw AddressAlreadyExists::withId($addressId);
        }

        $makeDefault = $makeDefault || $this->addresses === [];

        if ($makeDefault) {
            $this->clearDefaultAddress();
        }

        $this->addresses[$addressId->value()] = new Address(
            id: $addressId,
            postalAddress: $postalAddress,
            default: $makeDefault,
        );
    }

    public function updateAddress(
        AddressId $addressId,
        PostalAddress $postalAddress,
    ): void {
        $this->guardActive();
        $this->address($addressId)->replaceWith($postalAddress);
    }

    public function makeAddressDefault(AddressId $addressId): void
    {
        $this->guardActive();
        $address = $this->address($addressId);

        $this->clearDefaultAddress();
        $address->makeDefault();
    }

    public function removeAddress(AddressId $addressId): void
    {
        $this->guardActive();
        $address = $this->address($addressId);

        unset($this->addresses[$addressId->value()]);

        if ($address->isDefault() && $this->addresses !== []) {
            reset($this->addresses)->makeDefault();
        }
    }

    public function archive(): void
    {
        $this->status = CustomerStatus::Archived;
    }

    public function restore(): void
    {
        $this->status = CustomerStatus::Active;
    }

    private function guardActive(): void
    {
        if ($this->status === CustomerStatus::Archived) {
            throw CustomerArchived::cannotBeChanged();
        }
    }

    private function address(AddressId $addressId): Address
    {
        return $this->addresses[$addressId->value()]
            ?? throw AddressNotFound::withId($addressId);
    }

    private function clearDefaultAddress(): void
    {
        foreach ($this->addresses as $address) {
            $address->clearDefault();
        }
    }

    private function addHydratedAddress(Address $address): void
    {
        if (isset($this->addresses[$address->id()->value()])) {
            throw AddressAlreadyExists::withId($address->id());
        }

        if ($address->isDefault()) {
            $this->clearDefaultAddress();
        }

        $this->addresses[$address->id()->value()] = $address;
    }
}
