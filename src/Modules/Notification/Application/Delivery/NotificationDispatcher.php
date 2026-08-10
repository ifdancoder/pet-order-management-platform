<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Delivery;

use Modules\Notification\Application\Data\NotificationDispatchResult;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class NotificationDispatcher
{
    public function __construct(
        private INotificationRepository $notifications,
        private NotificationChannelResolver $channels,
        private LoggerInterface $logger,
        private int $batchSize,
        private int $claimTimeoutSeconds,
        private int $maximumAttempts,
        private int $initialRetryDelaySeconds,
        private int $maximumRetryDelaySeconds,
    ) {}

    public function dispatchPending(): NotificationDispatchResult
    {
        $notifications = $this->notifications->claimBatch(
            $this->batchSize,
            $this->claimTimeoutSeconds,
        );
        $sent = 0;
        $retrying = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                $this->channels->resolve($notification->channel)
                    ->send($notification);
                $this->notifications->markSent(
                    $notification->notificationId,
                    $notification->claimToken,
                );
                $sent++;
                $this->logger->info('Notification delivery succeeded.', [
                    'notification_id' => $notification->notificationId,
                ]);
            } catch (Throwable $exception) {
                $terminal = $notification->attempts >= $this->maximumAttempts;
                $this->notifications->release(
                    notificationId: $notification->notificationId,
                    claimToken: $notification->claimToken,
                    error: $exception::class.': '.$exception->getMessage(),
                    delaySeconds: $this->retryDelay($notification->attempts),
                    terminal: $terminal,
                );

                if ($terminal) {
                    $failed++;
                } else {
                    $retrying++;
                }

                $this->logger->log($terminal ? 'error' : 'warning', sprintf(
                    'Notification delivery %s.',
                    $terminal ? 'failed' : 'retrying',
                ), [
                    'notification_id' => $notification->notificationId,
                    'attempts' => $notification->attempts,
                    'exception' => $exception::class,
                ]);
            }
        }

        return new NotificationDispatchResult(
            claimed: count($notifications),
            sent: $sent,
            retrying: $retrying,
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
