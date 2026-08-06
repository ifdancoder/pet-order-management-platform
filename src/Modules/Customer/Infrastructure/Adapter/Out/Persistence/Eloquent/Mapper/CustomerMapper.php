<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Illuminate\Support\Collection;
use Modules\Customer\Domain\Entity\Address;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\Enum\CustomerStatus;
use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Modules\Customer\Domain\ValueObject\PhoneNumber;
use Modules\Customer\Domain\ValueObject\PostalAddress;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerAddressModel;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;

final class CustomerMapper
{
    /**
     * @param  Collection<int, CustomerAddressModel>  $addressModels
     */
    public function toDomain(
        CustomerModel $customerModel,
        Collection $addressModels,
    ): Customer {
        return new Customer(
            id: new CustomerId($customerModel->id),
            identityUserId: new IdentityUserId($customerModel->identity_user_id),
            name: new CustomerName(
                $customerModel->given_name,
                $customerModel->family_name,
            ),
            phoneNumber: $customerModel->phone_number === null
                ? null
                : new PhoneNumber($customerModel->phone_number),
            status: CustomerStatus::from($customerModel->status),
            addresses: array_values(
                $addressModels->map($this->addressToDomain(...))->all(),
            ),
        );
    }

    public function mapToModel(
        Customer $customer,
        CustomerModel $model,
    ): void {
        $model->id = $customer->id()->value();
        $model->identity_user_id = $customer->identityUserId()->value();
        $model->given_name = $customer->name()->givenName();
        $model->family_name = $customer->name()->familyName();
        $model->phone_number = $customer->phoneNumber()?->value();
        $model->status = $customer->status()->value;
    }

    public function mapAddressToModel(
        CustomerId $customerId,
        Address $address,
        CustomerAddressModel $model,
    ): void {
        $postalAddress = $address->postalAddress();

        $model->id = $address->id()->value();
        $model->customer_id = $customerId->value();
        $model->recipient_name = $postalAddress->recipientName();
        $model->line_1 = $postalAddress->line1();
        $model->line_2 = $postalAddress->line2();
        $model->city = $postalAddress->city();
        $model->region = $postalAddress->region();
        $model->postal_code = $postalAddress->postalCode();
        $model->country_code = $postalAddress->countryCode();
        $model->is_default = $address->isDefault();
    }

    private function addressToDomain(CustomerAddressModel $model): Address
    {
        return new Address(
            id: new AddressId($model->id),
            postalAddress: new PostalAddress(
                recipientName: $model->recipient_name,
                line1: $model->line_1,
                line2: $model->line_2,
                city: $model->city,
                region: $model->region,
                postalCode: $model->postal_code,
                countryCode: $model->country_code,
            ),
            default: $model->is_default,
        );
    }
}
