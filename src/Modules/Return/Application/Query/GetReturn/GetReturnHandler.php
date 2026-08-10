<?php

declare(strict_types=1);

namespace Modules\Return\Application\Query\GetReturn;

use InvalidArgumentException;
use Modules\Return\Application\Exception\ReturnNotFound;
use Modules\Return\Application\Port\Out\Customer\IReturnCustomerGateway;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\ValueObject\ReturnId;

final readonly class GetReturnHandler
{
    public function __construct(
        private IReturnRepository $returns,
        private IReturnCustomerGateway $customers,
    ) {}

    public function __invoke(GetReturnQuery $query): ReturnRequest
    {
        try {
            $return = $this->returns->findById(new ReturnId($query->returnId));
        } catch (InvalidArgumentException) {
            $return = null;
        }

        $customerId = $this->customers->customerIdForIdentity($query->identityUserId);

        if ($return === null || $customerId !== $return->customerId()) {
            throw ReturnNotFound::withId($query->returnId);
        }

        return $return;
    }
}
