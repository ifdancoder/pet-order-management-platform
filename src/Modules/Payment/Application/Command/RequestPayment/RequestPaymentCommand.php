<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Command\RequestPayment;

use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Payment> */
final readonly class RequestPaymentCommand implements ICommand
{
    public function __construct(
        public string $orderId,
        public string $identityUserId,
        public PaymentProvider $provider,
        public string $idempotencyKey,
        public string $paymentMethodReference,
    ) {}
}
