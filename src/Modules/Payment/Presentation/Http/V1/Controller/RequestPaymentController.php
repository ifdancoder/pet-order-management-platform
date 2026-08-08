<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\V1\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Application\Command\RequestPayment\RequestPaymentCommand;
use Modules\Payment\Presentation\Http\V1\Request\RequestPaymentRequest;
use Modules\Payment\Presentation\Http\V1\Resource\PaymentResource;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class RequestPaymentController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(
        RequestPaymentRequest $request,
        string $orderId,
    ): JsonResponse {
        $payment = $this->commandBus->dispatch(new RequestPaymentCommand(
            orderId: $orderId,
            identityUserId: $request->attributes->getString('identity.user_id'),
            provider: $request->provider(),
            idempotencyKey: $request->idempotencyKey(),
            paymentMethodReference: $request->paymentMethodReference(),
        ));

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }
}
