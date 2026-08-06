<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Customer\Application\Exception\CustomerAlreadyExists;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\CustomerMapper;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerAddressModel;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;

final readonly class EloquentCustomerRepository implements ICustomerRepository
{
    public function __construct(
        private CustomerMapper $mapper,
    ) {}

    public function save(Customer $customer): void
    {
        $customerModel = CustomerModel::query()->find($customer->id()->value())
            ?? new CustomerModel;

        $this->mapper->mapToModel($customer, $customerModel);

        try {
            $customerModel->save();
        } catch (UniqueConstraintViolationException $exception) {
            throw CustomerAlreadyExists::forIdentity(
                $customer->identityUserId()->value(),
                $exception,
            );
        }

        $addressIds = [];

        CustomerAddressModel::query()
            ->where('customer_id', $customer->id()->value())
            ->update(['is_default' => false]);

        foreach ($customer->addresses() as $address) {
            $addressIds[] = $address->id()->value();
            $addressModel = CustomerAddressModel::query()->find(
                $address->id()->value(),
            ) ?? new CustomerAddressModel;

            $this->mapper->mapAddressToModel(
                $customer->id(),
                $address,
                $addressModel,
            );
            $addressModel->save();
        }

        $obsoleteAddresses = CustomerAddressModel::query()
            ->where('customer_id', $customer->id()->value());

        if ($addressIds !== []) {
            $obsoleteAddresses->whereNotIn('id', $addressIds);
        }

        $obsoleteAddresses->delete();
    }

    public function findById(CustomerId $customerId): ?Customer
    {
        return $this->findOneBy('id', $customerId->value());
    }

    public function findByIdForUpdate(CustomerId $customerId): ?Customer
    {
        $model = CustomerModel::query()
            ->whereKey($customerId->value())
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->hydrate($model);
    }

    public function findByIdentityUserId(IdentityUserId $identityUserId): ?Customer
    {
        return $this->findOneBy(
            'identity_user_id',
            $identityUserId->value(),
        );
    }

    public function findByIdentityUserIdForUpdate(IdentityUserId $identityUserId): ?Customer
    {
        $model = CustomerModel::query()
            ->where('identity_user_id', $identityUserId->value())
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->hydrate($model);
    }

    public function existsByIdentityUserId(IdentityUserId $identityUserId): bool
    {
        return CustomerModel::query()
            ->where('identity_user_id', $identityUserId->value())
            ->exists();
    }

    private function findOneBy(string $column, string $value): ?Customer
    {
        $model = CustomerModel::query()->where($column, $value)->first();

        return $model === null ? null : $this->hydrate($model);
    }

    private function hydrate(CustomerModel $model): Customer
    {
        $addressModels = CustomerAddressModel::query()
            ->where('customer_id', $model->id)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $this->mapper->toDomain($model, $addressModels);
    }
}
