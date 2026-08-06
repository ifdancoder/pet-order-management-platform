<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\UpdateAddress;

use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class UpdateAddressHandler
{
    public function __construct(
        private ICustomerRepository $customers,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(UpdateAddressCommand $command): Customer
    {
        return $this->transaction->run(function () use ($command): Customer {
            $customer = $this->customers->findByIdentityUserIdForUpdate(
                new IdentityUserId($command->identityUserId),
            ) ?? throw CustomerNotFound::forIdentity($command->identityUserId);

            $customer->updateAddress(
                new AddressId($command->addressId),
                $command->address->toPostalAddress(),
            );
            $this->customers->save($customer);

            return $customer;
        });
    }
}
