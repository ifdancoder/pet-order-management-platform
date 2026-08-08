<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Persistence;

use Illuminate\Database\ConnectionInterface;
use Modules\Payment\Application\Port\Out\Persistence\IProcessedPaymentWebhookRepository;
use Modules\Payment\Domain\Enum\PaymentProvider;

final readonly class LaravelProcessedPaymentWebhookRepository implements IProcessedPaymentWebhookRepository
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function claim(PaymentProvider $provider, string $eventId): bool
    {
        return $this->connection
            ->table('processed_payment_webhooks')
            ->insertOrIgnore([
                'provider' => $provider->value,
                'event_id' => $eventId,
                'processed_at' => now()->toDateTimeString(),
            ]) === 1;
    }
}
