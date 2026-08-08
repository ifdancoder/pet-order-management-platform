<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Command\RequestPayment;

use Modules\Payment\Application\Data\GatewayPaymentRequest;
use Modules\Payment\Application\Data\GatewayPaymentResult;
use Modules\Payment\Application\Exception\PaymentIdempotencyConflict;
use Modules\Payment\Application\Exception\PaymentNotAllowed;
use Modules\Payment\Application\Exception\PaymentNotFound;
use Modules\Payment\Application\Port\Out\Gateway\IPaymentGatewayResolver;
use Modules\Payment\Application\Port\Out\Identity\IPaymentIdGenerator;
use Modules\Payment\Application\Port\Out\Order\IOrderPaymentGateway;
use Modules\Payment\Application\Port\Out\Persistence\IPaymentRepository;
use Modules\Payment\Domain\Entity\Payment;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Domain\ValueObject\IdempotencyKey;
use Modules\Payment\Domain\ValueObject\OrderId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Domain\ValueObject\Money;

final readonly class RequestPaymentHandler
{
    public function __construct(
        private IOrderPaymentGateway $orders,
        private IPaymentRepository $payments,
        private IPaymentIdGenerator $paymentIds,
        private IPaymentGatewayResolver $gateways,
        private PaymentRequestHasher $requestHasher,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RequestPaymentCommand $command): Payment
    {
        $order = $this->orders->findPayableOrder(
            $command->orderId,
            $command->identityUserId,
        ) ?? throw PaymentNotAllowed::forOrder($command->orderId);
        $idempotencyKey = new IdempotencyKey($command->idempotencyKey);
        $requestHash = $this->requestHasher->hash(
            $order,
            $command->provider,
            $command->paymentMethodReference,
        );

        $payment = $this->transaction->run(
            function () use ($command, $order, $idempotencyKey, $requestHash): Payment {
                $existing = $this->payments->findByIdempotencyKey($idempotencyKey);

                if ($existing !== null) {
                    return $this->resolveExisting($existing, $requestHash);
                }

                $payment = Payment::pending(
                    id: $this->paymentIds->generate(),
                    orderId: new OrderId($order->orderId),
                    amount: new Money($order->amount, $order->currency),
                    provider: $command->provider,
                    idempotencyKey: $idempotencyKey,
                    requestHash: $requestHash,
                );

                if ($this->payments->claim($payment)) {
                    return $payment;
                }

                $existing = $this->payments->findByIdempotencyKey($idempotencyKey)
                    ?? throw PaymentNotFound::withId($payment->id()->value());

                return $this->resolveExisting($existing, $requestHash);
            },
        );

        if ($payment->status() !== PaymentStatus::Pending) {
            return $payment;
        }

        $result = $this->gateways
            ->resolve($payment->provider())
            ->authorize(new GatewayPaymentRequest(
                paymentId: $payment->id()->value(),
                orderId: $payment->orderId()->value(),
                amount: $payment->amount()->amount(),
                currency: $payment->amount()->currency(),
                idempotencyKey: $payment->idempotencyKey()->value(),
                paymentMethodReference: $command->paymentMethodReference,
            ));

        return $this->applyGatewayResult($payment, $result);
    }

    private function applyGatewayResult(
        Payment $payment,
        GatewayPaymentResult $result,
    ): Payment {
        return $this->transaction->run(function () use ($payment, $result): Payment {
            $lockedPayment = $this->payments->findByIdForUpdate($payment->id())
                ?? throw PaymentNotFound::withId($payment->id()->value());

            if ($lockedPayment->status() !== PaymentStatus::Pending) {
                return $lockedPayment;
            }

            if ($result->succeeded && $result->providerPaymentId !== null) {
                $lockedPayment->authorize($result->providerPaymentId);
            } else {
                $lockedPayment->fail($result->failureCode ?? 'provider_failure');
            }

            $this->payments->save($lockedPayment);

            return $lockedPayment;
        });
    }

    private function resolveExisting(
        Payment $payment,
        string $requestHash,
    ): Payment {
        if (! hash_equals($payment->requestHash(), $requestHash)) {
            throw PaymentIdempotencyConflict::forKey(
                $payment->idempotencyKey()->value(),
            );
        }

        return $payment;
    }
}
