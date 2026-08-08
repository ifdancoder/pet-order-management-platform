<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Command\ProcessPaymentWebhook;

use Modules\Payment\Application\Data\PaymentWebhookRequest;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<null> */
final readonly class ProcessPaymentWebhookCommand implements ICommand
{
    public function __construct(
        public PaymentWebhookRequest $webhook,
    ) {}
}
