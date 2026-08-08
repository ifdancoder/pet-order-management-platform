<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Port\Out\Persistence;

use Modules\Payment\Domain\Enum\PaymentProvider;

interface IProcessedPaymentWebhookRepository
{
    public function claim(PaymentProvider $provider, string $eventId): bool;
}
