<?php

declare(strict_types=1);

use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\Enum\CustomerStatus;
use Modules\Customer\Domain\Exception\CustomerArchived;
use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Modules\Customer\Domain\ValueObject\PhoneNumber;
use Modules\Customer\Domain\ValueObject\PostalAddress;

function customerAggregate(): Customer
{
    return Customer::register(
        id: new CustomerId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f16'),
        identityUserId: new IdentityUserId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f17'),
        name: new CustomerName('Ada', 'Lovelace'),
        phoneNumber: new PhoneNumber('+12025550123'),
    );
}

function customerPostalAddress(string $city = 'London'): PostalAddress
{
    return new PostalAddress(
        recipientName: 'Ada Lovelace',
        line1: '1 Analytical Engine Road',
        line2: null,
        city: $city,
        region: null,
        postalCode: 'SW1A 1AA',
        countryCode: 'GB',
    );
}

it('registers an active customer linked to an identity by id', function () {
    $customer = customerAggregate();

    expect($customer->status())->toBe(CustomerStatus::Active)
        ->and($customer->identityUserId()->value())
        ->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f17');
});

it('maintains exactly one default address when addresses exist', function () {
    $customer = customerAggregate();
    $firstId = new AddressId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f18');
    $secondId = new AddressId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f19');

    $customer->addAddress($firstId, customerPostalAddress());
    $customer->addAddress($secondId, customerPostalAddress('Paris'), true);

    expect($customer->addresses())->toHaveCount(2)
        ->and($customer->addresses()[0]->isDefault())->toBeFalse()
        ->and($customer->addresses()[1]->isDefault())->toBeTrue();

    $customer->removeAddress($secondId);

    expect($customer->addresses())->toHaveCount(1)
        ->and($customer->addresses()[0]->isDefault())->toBeTrue();
});

it('updates profile and address through aggregate behavior', function () {
    $customer = customerAggregate();
    $addressId = new AddressId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f18');
    $customer->addAddress($addressId, customerPostalAddress());

    $customer->updateProfile(
        new CustomerName('Grace', 'Hopper'),
        null,
    );
    $customer->updateAddress($addressId, customerPostalAddress('New York'));

    expect($customer->name()->givenName())->toBe('Grace')
        ->and($customer->phoneNumber())->toBeNull()
        ->and($customer->addresses()[0]->postalAddress()->city())->toBe('New York');
});

it('prevents profile and address changes while archived', function () {
    $customer = customerAggregate();
    $customer->archive();

    $customer->updateProfile(
        new CustomerName('Grace', 'Hopper'),
        null,
    );
})->throws(CustomerArchived::class);
