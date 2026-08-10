<?php

declare(strict_types=1);

namespace Modules\Return\Presentation\Http\V1\Controller;

use Illuminate\Http\Request;
use Modules\Return\Application\Query\GetReturn\GetReturnQuery;
use Modules\Return\Presentation\Http\V1\Resource\ReturnResource;
use Shared\Application\Bus\Query\IQueryBus;

final readonly class GetReturnController
{
    public function __construct(
        private IQueryBus $queries,
    ) {}

    public function __invoke(Request $request, string $returnId): ReturnResource
    {
        return new ReturnResource($this->queries->ask(new GetReturnQuery(
            returnId: $returnId,
            identityUserId: $request->attributes->getString('identity.user_id'),
        )));
    }
}
