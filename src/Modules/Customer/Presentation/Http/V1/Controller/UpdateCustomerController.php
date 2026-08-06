<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Modules\Customer\Application\Command\UpdateCustomer\UpdateCustomerCommand;
use Modules\Customer\Presentation\Http\V1\Request\SaveCustomerProfileRequest;
use Modules\Customer\Presentation\Http\V1\Resource\CustomerResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class UpdateCustomerController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(SaveCustomerProfileRequest $request): CustomerResource
    {
        $customer = $this->commandBus->dispatch(new UpdateCustomerCommand(
            identityUserId: $request->attributes->getString('identity.user_id'),
            givenName: $request->givenName(),
            familyName: $request->familyName(),
            phoneNumber: $request->phoneNumber(),
        ));

        return new CustomerResource($customer);
    }
}
