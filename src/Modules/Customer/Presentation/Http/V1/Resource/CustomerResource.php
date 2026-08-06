<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Domain\Entity\Address;
use Modules\Customer\Domain\Entity\Customer;

final class CustomerResource extends JsonResource
{
    private Customer $customer;

    public function __construct(Customer $resource)
    {
        parent::__construct($resource);

        $this->customer = $resource;
    }

    /**
     * @return array{id: string, identity_user_id: string, given_name: string, family_name: string, phone_number: ?string, status: string, addresses: list<AddressResource>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->customer->id()->value(),
            'identity_user_id' => $this->customer->identityUserId()->value(),
            'given_name' => $this->customer->name()->givenName(),
            'family_name' => $this->customer->name()->familyName(),
            'phone_number' => $this->customer->phoneNumber()?->value(),
            'status' => $this->customer->status()->value,
            'addresses' => array_map(
                static fn (Address $address): AddressResource => new AddressResource($address),
                $this->customer->addresses(),
            ),
        ];
    }
}
