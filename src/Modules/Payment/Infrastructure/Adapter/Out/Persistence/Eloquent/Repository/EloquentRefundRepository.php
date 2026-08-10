<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Payment\Application\Data\RefundAttempt;
use Modules\Payment\Application\Port\Out\Persistence\IRefundRepository;
use Modules\Payment\Domain\Entity\PaymentRefund;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\RefundStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentRefundModel;
use RuntimeException;
use stdClass;

final readonly class EloquentRefundRepository implements IRefundRepository
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    public function claim(PaymentRefund $refund): bool
    {
        return PaymentRefundModel::query()->insertOrIgnore([
            'id' => $refund->id()->value(),
            'return_id' => $refund->returnId(),
            'payment_id' => $refund->paymentId()->value(),
            'order_id' => $refund->orderId(),
            'amount' => $refund->amount()->amount(),
            'currency' => $refund->amount()->currency(),
            'status' => $refund->status()->value,
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    public function claimBatch(int $limit, int $claimTimeoutSeconds): array
    {
        if ($limit < 1 || $claimTimeoutSeconds < 1) {
            throw new RuntimeException('Refund claim configuration must be positive.');
        }

        $connection = $this->database->connection();

        return $connection->transaction(function () use ($connection, $limit, $claimTimeoutSeconds): array {
            $now = now();
            $query = $connection->table('payment_refunds as refunds')
                ->join('payments', 'payments.id', '=', 'refunds.payment_id')
                ->where('refunds.status', RefundStatus::Pending->value)
                ->where('refunds.available_at', '<=', $now)
                ->whereNotNull('payments.provider_payment_id')
                ->where(function ($query) use ($now, $claimTimeoutSeconds): void {
                    $query->whereNull('refunds.claimed_at')->orWhere(
                        'refunds.claimed_at',
                        '<=',
                        $now->copy()->subSeconds($claimTimeoutSeconds),
                    );
                })
                ->orderBy('refunds.id')
                ->limit($limit)
                ->select([
                    'refunds.id',
                    'refunds.return_id',
                    'refunds.amount',
                    'refunds.currency',
                    'refunds.attempts',
                    'payments.provider',
                    'payments.provider_payment_id',
                ]);

            $connection->getDriverName() === 'pgsql'
                ? $query->lock('FOR UPDATE OF refunds SKIP LOCKED')
                : $query->lockForUpdate();

            /** @var Collection<int, stdClass> $rows */
            $rows = $query->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $claimToken = Str::uuid7()->toString();
            $ids = $rows->map(static fn (stdClass $row): string => (string) $row->id)->all();
            $connection->table('payment_refunds')->whereIn('id', $ids)->update([
                'claim_token' => $claimToken,
                'claimed_at' => $now,
                'attempts' => $connection->raw('attempts + 1'),
                'last_error' => null,
                'updated_at' => $now,
            ]);

            return array_values($rows->map(
                static fn (stdClass $row): RefundAttempt => new RefundAttempt(
                    refundId: (string) $row->id,
                    returnId: (string) $row->return_id,
                    claimToken: $claimToken,
                    providerPaymentId: (string) $row->provider_payment_id,
                    provider: PaymentProvider::from((string) $row->provider),
                    amount: (int) $row->amount,
                    currency: (string) $row->currency,
                    attempts: (int) $row->attempts + 1,
                ),
            )->all());
        });
    }

    public function markCompleted(
        string $refundId,
        string $claimToken,
        string $providerRefundId,
    ): void {
        $updated = $this->claimed($refundId, $claimToken)->update([
            'status' => RefundStatus::Completed->value,
            'provider_refund_id' => $providerRefundId,
            'claimed_at' => null,
            'claim_token' => null,
            'last_error' => null,
            'updated_at' => now(),
        ]);
        $this->guardUpdated($updated);
    }

    public function release(
        string $refundId,
        string $claimToken,
        string $error,
        int $delaySeconds,
        bool $terminal,
    ): void {
        $now = now();
        $updated = $this->claimed($refundId, $claimToken)->update([
            'status' => $terminal ? RefundStatus::Failed->value : RefundStatus::Pending->value,
            'available_at' => $now->copy()->addSeconds($delaySeconds),
            'claimed_at' => null,
            'claim_token' => null,
            'last_error' => Str::limit($error, 2000, ''),
            'updated_at' => $now,
        ]);
        $this->guardUpdated($updated);
    }

    private function claimed(string $refundId, string $claimToken): Builder
    {
        return $this->database->table('payment_refunds')
            ->where('id', $refundId)
            ->where('claim_token', $claimToken)
            ->where('status', RefundStatus::Pending->value);
    }

    private function guardUpdated(int $updated): void
    {
        if ($updated !== 1) {
            throw new RuntimeException('Refund claim was lost.');
        }
    }
}
