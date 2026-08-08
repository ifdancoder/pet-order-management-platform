<?php

declare(strict_types=1);

namespace Shared\Application\Outbox;

use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use Throwable;

final readonly class OutboxPublisher
{
    public function __construct(
        private IOutboxRepository $outbox,
        private IMessagePublisher $messages,
        private int $batchSize,
        private int $claimTimeoutSeconds,
        private int $initialRetryDelaySeconds,
        private int $maximumRetryDelaySeconds,
    ) {}

    public function publishPending(): OutboxPublishResult
    {
        $messages = $this->outbox->claimBatch(
            $this->batchSize,
            $this->claimTimeoutSeconds,
        );
        $published = 0;
        $failed = 0;

        foreach ($messages as $message) {
            try {
                $this->messages->publish($message);
                $this->outbox->markPublished(
                    $message->messageId,
                    $message->claimToken,
                );
                $published++;
            } catch (Throwable $exception) {
                $this->outbox->release(
                    messageId: $message->messageId,
                    claimToken: $message->claimToken,
                    error: $exception::class.': '.$exception->getMessage(),
                    delaySeconds: $this->retryDelay($message->attempts),
                );
                $failed++;
            }
        }

        return new OutboxPublishResult(
            claimed: count($messages),
            published: $published,
            failed: $failed,
        );
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
