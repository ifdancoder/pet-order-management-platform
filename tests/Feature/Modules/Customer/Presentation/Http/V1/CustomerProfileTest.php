<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);

    $this->customerIdentity = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
});

function customerApiProfilePayload(): array
{
    return [
        'given_name' => 'Ada',
        'family_name' => 'Lovelace',
        'phone_number' => '+12025550123',
    ];
}

function customerApiAddressPayload(string $city = 'London'): array
{
    return [
        'recipient_name' => 'Ada Lovelace',
        'line_1' => '1 Analytical Engine Road',
        'line_2' => null,
        'city' => $city,
        'region' => null,
        'postal_code' => 'SW1A 1AA',
        'country_code' => 'GB',
    ];
}

it('manages the authenticated customer profile and addresses', function () {
    $token = 'access-token-'.$this->customerIdentity->getKey();

    $registerResponse = $this->withToken($token)
        ->postJson(route('customers.profile.store'), customerApiProfilePayload())
        ->assertCreated()
        ->assertJsonPath('data.identity_user_id', $this->customerIdentity->getKey())
        ->assertJsonPath('data.given_name', 'Ada')
        ->assertJsonPath('data.addresses', []);

    $customerId = $registerResponse->json('data.id');

    $this->withToken($token)
        ->patchJson(route('customers.profile.update'), [
            'given_name' => 'Grace',
            'family_name' => 'Hopper',
            'phone_number' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.id', $customerId)
        ->assertJsonPath('data.given_name', 'Grace')
        ->assertJsonPath('data.phone_number', null);

    $firstAddressId = $this->withToken($token)
        ->postJson(
            route('customers.profile.addresses.store'),
            customerApiAddressPayload(),
        )
        ->assertCreated()
        ->assertJsonPath('data.addresses.0.is_default', true)
        ->json('data.addresses.0.id');

    $secondAddressId = $this->withToken($token)
        ->postJson(
            route('customers.profile.addresses.store'),
            customerApiAddressPayload('Paris') + ['make_default' => true],
        )
        ->assertCreated()
        ->assertJsonPath('data.addresses.0.is_default', false)
        ->assertJsonPath('data.addresses.1.is_default', true)
        ->json('data.addresses.1.id');

    $this->withToken($token)
        ->putJson(
            route('customers.profile.addresses.update', ['addressId' => $firstAddressId]),
            customerApiAddressPayload('New York'),
        )
        ->assertOk()
        ->assertJsonFragment([
            'id' => $firstAddressId,
            'city' => 'New York',
        ]);

    $this->withToken($token)
        ->postJson(route('customers.profile.addresses.default', [
            'addressId' => $firstAddressId,
        ]))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $firstAddressId,
            'is_default' => true,
        ])
        ->assertJsonFragment([
            'id' => $secondAddressId,
            'is_default' => false,
        ]);

    $this->withToken($token)
        ->deleteJson(route('customers.profile.addresses.destroy', [
            'addressId' => $firstAddressId,
        ]))
        ->assertNoContent();

    $this->withToken($token)
        ->getJson(route('customers.profile.show'))
        ->assertOk()
        ->assertJsonCount(1, 'data.addresses')
        ->assertJsonPath('data.addresses.0.id', $secondAddressId)
        ->assertJsonPath('data.addresses.0.is_default', true);
});

it('prevents duplicate profiles for one identity', function () {
    $token = 'access-token-'.$this->customerIdentity->getKey();

    $this->withToken($token)
        ->postJson(route('customers.profile.store'), customerApiProfilePayload())
        ->assertCreated();

    $this->withToken($token)
        ->postJson(route('customers.profile.store'), customerApiProfilePayload())
        ->assertConflict()
        ->assertExactJson([
            'error' => [
                'code' => 'customer_already_exists',
                'message' => 'A customer profile already exists.',
            ],
        ]);
});

it('isolates profiles by the authenticated identity id', function () {
    $this->withToken('access-token-'.$this->customerIdentity->getKey())
        ->postJson(route('customers.profile.store'), customerApiProfilePayload())
        ->assertCreated();

    $otherIdentity = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);

    $this->withToken('access-token-'.$otherIdentity->getKey())
        ->getJson(route('customers.profile.show'))
        ->assertNotFound()
        ->assertExactJson([
            'error' => [
                'code' => 'customer_not_found',
                'message' => 'Customer was not found.',
            ],
        ]);
});

it('requires a valid access token', function () {
    $this->getJson(route('customers.profile.show'))
        ->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'invalid_access_token',
                'message' => 'Access token is invalid.',
            ],
        ]);
});

it('validates profile and address boundaries', function () {
    $token = 'access-token-'.$this->customerIdentity->getKey();

    $this->withToken($token)
        ->postJson(route('customers.profile.store'), [
            'given_name' => '',
            'family_name' => '',
            'phone_number' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'given_name',
            'family_name',
            'phone_number',
        ]);

    $this->withToken($token)
        ->postJson(route('customers.profile.store'), customerApiProfilePayload())
        ->assertCreated();

    $this->withToken($token)
        ->postJson(route('customers.profile.addresses.store'), [
            'country_code' => 'gb',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'recipient_name',
            'line_1',
            'city',
            'postal_code',
            'country_code',
        ]);
});
