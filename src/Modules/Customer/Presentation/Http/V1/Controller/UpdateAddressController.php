<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Modules\Customer\Application\Command\UpdateAddress\UpdateAddressCommand;
use Modules\Customer\Presentation\Http\V1\Request\SaveAddressRequest;
use Modules\Customer\Presentation\Http\V1\Resource\CustomerResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class UpdateAddressController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(
        SaveAddressRequest $request,
        string $addressId,
    ): CustomerResource {
        $customer = $this->commandBus->dispatch(new UpdateAddressCommand(
            identityUserId: $request->attributes->getString('identity.user_id'),
            addressId: $addressId,
            address: $request->address(),
        ));

        return new CustomerResource($customer);
    }
}
