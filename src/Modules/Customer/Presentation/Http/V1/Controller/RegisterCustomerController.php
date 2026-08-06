<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Customer\Application\Command\RegisterCustomer\RegisterCustomerCommand;
use Modules\Customer\Presentation\Http\V1\Request\SaveCustomerProfileRequest;
use Modules\Customer\Presentation\Http\V1\Resource\CustomerResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RegisterCustomerController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(SaveCustomerProfileRequest $request): JsonResponse
    {
        $customer = $this->commandBus->dispatch(new RegisterCustomerCommand(
            identityUserId: $request->attributes->getString('identity.user_id'),
            givenName: $request->givenName(),
            familyName: $request->familyName(),
            phoneNumber: $request->phoneNumber(),
        ));

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }
}
