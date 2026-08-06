<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Domain\Entity\Address;

final class AddressResource extends JsonResource
{
    private Address $address;

    public function __construct(Address $resource)
    {
        parent::__construct($resource);

        $this->address = $resource;
    }

    /**
     * @return array{id: string, recipient_name: string, line_1: string, line_2: ?string, city: string, region: ?string, postal_code: string, country_code: string, is_default: bool}
     */
    public function toArray(Request $request): array
    {
        $postalAddress = $this->address->postalAddress();

        return [
            'id' => $this->address->id()->value(),
            'recipient_name' => $postalAddress->recipientName(),
            'line_1' => $postalAddress->line1(),
            'line_2' => $postalAddress->line2(),
            'city' => $postalAddress->city(),
            'region' => $postalAddress->region(),
            'postal_code' => $postalAddress->postalCode(),
            'country_code' => $postalAddress->countryCode(),
            'is_default' => $this->address->isDefault(),
        ];
    }
}
