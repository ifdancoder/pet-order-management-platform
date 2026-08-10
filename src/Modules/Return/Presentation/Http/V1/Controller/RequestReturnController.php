<?php

declare(strict_types=1);

namespace Modules\Return\Presentation\Http\V1\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Return\Application\Command\RequestReturn\RequestReturnCommand;
use Modules\Return\Presentation\Http\V1\Request\RequestReturnRequest;
use Modules\Return\Presentation\Http\V1\Resource\ReturnResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RequestReturnController
{
    public function __construct(
        private ICommandBus $commands,
    ) {}

    public function __invoke(RequestReturnRequest $request): JsonResponse
    {
        $return = $this->commands->dispatch(new RequestReturnCommand(
            orderId: $request->orderId(),
            identityUserId: $request->attributes->getString('identity.user_id'),
            reason: $request->reason(),
            items: $request->items(),
        ));

        return (new ReturnResource($return))->response()->setStatusCode(201);
    }
}
