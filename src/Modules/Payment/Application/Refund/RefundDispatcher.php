<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Refund;

use Modules\Payment\Application\Data\RefundDispatchResult;
use Modules\Payment\Application\Event\RefundIntegrationEvent;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGatewayResolver;
use Modules\Payment\Application\Port\Out\Persistence\IRefundRepository;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Throwable;

final readonly class RefundDispatcher
{
    public function __construct(
        private IRefundRepository $refunds,
        private IPaymentGatewayResolver $gateways,
        private ITransactionManager $transaction,
        private IOutboxWriter $outbox,
        private int $batchSize,
        private int $claimTimeoutSeconds,
        private int $maximumAttempts,
        private int $initialRetryDelaySeconds,
        private int $maximumRetryDelaySeconds,
    ) {}

    public function dispatchPending(): RefundDispatchResult
    {
        $refunds = $this->refunds->claimBatch($this->batchSize, $this->claimTimeoutSeconds);
        $completed = 0;
        $retrying = 0;
        $failed = 0;

        foreach ($refunds as $refund) {
            try {
                $result = $this->gateways->resolve($refund->provider)->refund(
                    providerPaymentId: $refund->providerPaymentId,
                    amount: $refund->amount,
                    currency: $refund->currency,
                    idempotencyKey: $refund->refundId,
                );

                if (! $result->succeeded || $result->providerPaymentId === null) {
                    throw new \RuntimeException($result->failureCode ?? 'Refund provider failure.');
                }

                $this->transaction->run(function () use ($refund, $result): void {
                    $this->refunds->markCompleted(
                        $refund->refundId,
                        $refund->claimToken,
                        $result->providerPaymentId,
                    );
                    $this->outbox->record(new RefundIntegrationEvent(
                        refundId: $refund->refundId,
                        returnId: $refund->returnId,
                        amount: $refund->amount,
                        currency: $refund->currency,
                    ));
                });
                $completed++;
            } catch (Throwable $exception) {
                $terminal = $refund->attempts >= $this->maximumAttempts;
                $this->refunds->release(
                    refundId: $refund->refundId,
                    claimToken: $refund->claimToken,
                    error: $exception::class.': '.$exception->getMessage(),
                    delaySeconds: $this->retryDelay($refund->attempts),
                    terminal: $terminal,
                );
                $terminal ? $failed++ : $retrying++;
            }
        }

        return new RefundDispatchResult(count($refunds), $completed, $retrying, $failed);
    }

    private function retryDelay(int $attempts): int
    {
        $exponent = min(max($attempts - 1, 0), 20);

        return min(
            $this->initialRetryDelaySeconds * (2 ** $exponent),
            $this->maximumRetryDelaySeconds,
        );
    }
}
