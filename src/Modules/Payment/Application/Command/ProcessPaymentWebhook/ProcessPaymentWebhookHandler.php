<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Command\ProcessPaymentWebhook;

use Modules\Payment\Application\Data\VerifiedPaymentWebhook;
use Modules\Payment\Application\Event\PaymentIntegrationEvent;
use Modules\Payment\Application\Exception\InvalidPaymentWebhook;
use Modules\Payment\Application\Exception\PaymentNotFound;
use Modules\Payment\Application\Port\Out\Persistence\IPaymentRepository;
use Modules\Payment\Application\Port\Out\Persistence\IProcessedPaymentWebhookRepository;
use Modules\Payment\Application\Port\Out\Webhook\IPaymentWebhookVerifierResolver;
use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Domain\Exception\InvalidPaymentStatusTransition;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class ProcessPaymentWebhookHandler
{
    public function __construct(
        private IPaymentWebhookVerifierResolver $verifiers,
        private IPaymentRepository $payments,
        private IProcessedPaymentWebhookRepository $processedWebhooks,
        private ITransactionManager $transaction,
        private IOutboxWriter $outbox,
    ) {}

    public function __invoke(ProcessPaymentWebhookCommand $command): null
    {
        $event = $this->verifiers
            ->resolve($command->webhook->provider)
            ->verify($command->webhook);

        $this->transaction->run(function () use ($event): void {
            if (! $this->processedWebhooks->claim($event->provider, $event->eventId)) {
                return;
            }

            $payment = $this->payments->findByProviderPaymentIdForUpdate(
                $event->provider,
                $event->lookupProviderPaymentId,
            ) ?? throw PaymentNotFound::withId($event->lookupProviderPaymentId);

            try {
                $changed = $this->apply($payment, $event);
            } catch (InvalidPaymentStatusTransition $exception) {
                throw InvalidPaymentWebhook::create($exception);
            }

            if ($changed) {
                $this->payments->save($payment);
                $this->outbox->record(
                    PaymentIntegrationEvent::fromPayment($payment),
                );
            }
        });

        return null;
    }

    private function apply(Payment $payment, VerifiedPaymentWebhook $event): bool
    {
        if ($payment->provider() !== $event->provider) {
            throw InvalidPaymentWebhook::create();
        }

        if ($payment->status() === $event->status) {
            return false;
        }

        match ($event->status) {
            PaymentStatus::Authorized => $payment->authorize($event->providerPaymentId),
            PaymentStatus::Captured => $payment->capture($event->providerPaymentId),
            PaymentStatus::Failed => $payment->fail(
                $event->failureCode ?? 'provider_failure',
            ),
            PaymentStatus::Refunded => $payment->refund(),
            PaymentStatus::Pending => throw InvalidPaymentWebhook::create(),
        };

        return true;
    }
}
