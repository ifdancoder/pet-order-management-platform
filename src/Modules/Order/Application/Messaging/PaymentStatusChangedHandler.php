<?php

declare(strict_types=1);

namespace Modules\Order\Application\Messaging;

use InvalidArgumentException;
use Modules\Order\Application\Exception\OrderNotFound;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Domain\ValueObject\OrderId;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;

final readonly class PaymentStatusChangedHandler implements IIntegrationMessageHandler
{
    public function __construct(
        private IOrderRepository $orders,
    ) {}

    public function consumerName(): string
    {
        return 'order.payment-status';
    }

    public function messageNames(): array
    {
        return [
            'payment.captured.v1',
            'payment.failed.v1',
        ];
    }

    public function handle(IntegrationMessage $message): void
    {
        $orderId = $message->data['order_id'] ?? null;

        if (! is_string($orderId)) {
            throw new InvalidArgumentException('Payment message order_id must be a string.');
        }

        $order = $this->orders->findByIdForUpdate(new OrderId($orderId))
            ?? throw OrderNotFound::withId($orderId);

        match ($message->name) {
            'payment.captured.v1' => $order->confirm(),
            'payment.failed.v1' => $order->markPaymentFailed(),
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported payment message "%s".',
                $message->name,
            )),
        };

        $this->orders->save($order);
    }
}
