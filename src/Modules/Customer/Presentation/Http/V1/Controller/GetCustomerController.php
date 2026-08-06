<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Controller;

use Illuminate\Http\Request;
use Modules\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentityQuery;
use Modules\Customer\Presentation\Http\V1\Resource\CustomerResource;
use Shared\Application\Bus\Query\IQueryBus;

final readonly class GetCustomerController
{
    public function __construct(
        private IQueryBus $queryBus,
    ) {}

    public function __invoke(Request $request): CustomerResource
    {
        $customer = $this->queryBus->ask(new GetCustomerByIdentityQuery(
            identityUserId: $request->attributes->getString('identity.user_id'),
        ));

        return new CustomerResource($customer);
    }
}
