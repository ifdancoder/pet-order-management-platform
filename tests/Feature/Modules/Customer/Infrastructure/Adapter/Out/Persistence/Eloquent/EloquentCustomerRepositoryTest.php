<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Application\Exception\CustomerAlreadyExists;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Modules\Customer\Domain\ValueObject\PostalAddress;

uses(RefreshDatabase::class);

function persistedCustomer(
    string $customerId = '018f22e2-7c2a-7a33-8c4c-4ea690ad4f16',
    string $identityUserId = '018f22e2-7c2a-7a33-8c4c-4ea690ad4f17',
): Customer {
    return Customer::register(
        id: new CustomerId($customerId),
        identityUserId: new IdentityUserId($identityUserId),
        name: new CustomerName('Ada', 'Lovelace'),
        phoneNumber: null,
    );
}

function persistedPostalAddress(string $city): PostalAddress
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

it('persists and rehydrates the customer aggregate', function () {
    $repository = app(ICustomerRepository::class);
    $customer = persistedCustomer();
    $customer->addAddress(
        new AddressId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f18'),
        persistedPostalAddress('London'),
    );
    $customer->addAddress(
        new AddressId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f19'),
        persistedPostalAddress('Paris'),
        true,
    );

    $repository->save($customer);

    $rehydrated = $repository->findByIdentityUserId(
        $customer->identityUserId(),
    );

    expect($rehydrated)->not->toBeNull()
        ->and($rehydrated?->name()->givenName())->toBe('Ada')
        ->and($rehydrated?->addresses())->toHaveCount(2)
        ->and($rehydrated?->addresses()[0]->postalAddress()->city())->toBe('Paris')
        ->and($rehydrated?->addresses()[0]->isDefault())->toBeTrue();
});

it('maps the unique identity constraint to an application failure', function () {
    $repository = app(ICustomerRepository::class);
    $repository->save(persistedCustomer());

    $repository->save(persistedCustomer(
        customerId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f20',
    ));
})->throws(CustomerAlreadyExists::class);

it('enforces one default address per customer in the database', function () {
    $customer = persistedCustomer();
    app(ICustomerRepository::class)->save($customer);

    $attributes = [
        'customer_id' => $customer->id()->value(),
        'recipient_name' => 'Ada Lovelace',
        'line_1' => '1 Analytical Engine Road',
        'line_2' => null,
        'city' => 'London',
        'region' => null,
        'postal_code' => 'SW1A 1AA',
        'country_code' => 'GB',
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('customer_addresses')->insert($attributes + [
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f18',
    ]);
    DB::table('customer_addresses')->insert($attributes + [
        'id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f19',
    ]);
})->throws(QueryException::class);
