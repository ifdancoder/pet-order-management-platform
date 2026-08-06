<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Illuminate\Http\Request;
use Modules\Customer\Application\Command\MakeAddressDefault\MakeAddressDefaultCommand;
use Modules\Customer\Presentation\Http\V1\Resource\CustomerResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class MakeAddressDefaultController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, string $addressId): CustomerResource
    {
        $customer = $this->commandBus->dispatch(new MakeAddressDefaultCommand(
            identityUserId: $request->attributes->getString('identity.user_id'),
            addressId: $addressId,
        ));

        return new CustomerResource($customer);
    }
}
