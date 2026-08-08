<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\V1\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Payment\Application\Command\ProcessPaymentWebhook\ProcessPaymentWebhookCommand;
use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Shared\Application\Bus\Command\ICommandBus;

final readonly class PaymentWebhookController
{
    public function __construct(
        private ICommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, string $provider): Response
    {
        $paymentProvider = PaymentProvider::tryFrom($provider)
            ?? throw InvalidPaymentWebhook::create();

        $this->commandBus->dispatch(new ProcessPaymentWebhookCommand(
            new PaymentWebhookRequest(
                provider: $paymentProvider,
                payload: $request->getContent(),
                signature: $paymentProvider === PaymentProvider::Stripe
                    ? $request->headers->get('Stripe-Signature')
                    : $request->headers->get('PayPal-Transmission-Sig'),
                transmissionId: $request->headers->get('PayPal-Transmission-Id'),
                transmissionTime: $request->headers->get('PayPal-Transmission-Time'),
                certificateUrl: $request->headers->get('PayPal-Cert-Url'),
                authAlgorithm: $request->headers->get('PayPal-Auth-Algo'),
            ),
        ));

        return response()->noContent();
    }
}
