<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Customer\Application\Command\RemoveAddress\RemoveAddressCommand;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RemoveAddressController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, string $addressId): Response
    {
        $this->commandBus->dispatch(new RemoveAddressCommand(
            identityUserId: $request->attributes->getString('identity.user_id'),
            addressId: $addressId,
        ));

        return response()->noContent();
    }
}
