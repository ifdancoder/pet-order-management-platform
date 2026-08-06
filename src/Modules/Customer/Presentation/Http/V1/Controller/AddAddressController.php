<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Customer\Application\Command\AddAddress\AddAddressCommand;
use Modules\Customer\Presentation\Http\V1\Request\SaveAddressRequest;
use Modules\Customer\Presentation\Http\V1\Resource\CustomerResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class AddAddressController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(SaveAddressRequest $request): JsonResponse
    {
        $customer = $this->commandBus->dispatch(new AddAddressCommand(
            identityUserId: $request->attributes->getString('identity.user_id'),
            address: $request->address(),
            makeDefault: $request->makeDefault(),
        ));

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }
}
