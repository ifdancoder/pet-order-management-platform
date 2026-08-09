<?php

declare(strict_types=1);

namespace Shared\Application\Messaging;

use LogicException;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Application\Port\Out\Messaging\IProcessedMessageStore;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final class IntegrationMessageConsumer
{
    /** @var array<string, array<string, IIntegrationMessageHandler>> */
    private array $handlers = [];

    /** @param iterable<IIntegrationMessageHandler> $handlers */
    public function __construct(
        iterable $handlers,
        private readonly IProcessedMessageStore $processedMessages,
        private readonly ITransactionManager $transaction,
    ) {
        foreach ($handlers as $handler) {
            $consumerName = $handler->consumerName();

            foreach ($handler->messageNames() as $messageName) {
                if (isset($this->handlers[$consumerName][$messageName])) {
                    throw new LogicException(sprintf(
                        'Consumer "%s" has multiple handlers for integration message "%s".',
                        $consumerName,
                        $messageName,
                    ));
                }

                $this->handlers[$consumerName][$messageName] = $handler;
            }
        }
    }

    public function consume(
        string $consumerName,
        IntegrationMessage $message,
    ): bool {
        $handler = $this->handlers[$consumerName][$message->name]
            ?? throw new LogicException(sprintf(
                'Consumer "%s" has no handler for integration message "%s".',
                $consumerName,
                $message->name,
            ));

        return $this->transaction->run(function () use ($handler, $message): bool {
            if (! $this->processedMessages->record(
                $handler->consumerName(),
                $message->messageId,
            )) {
                return false;
            }

            $handler->handle($message);

            return true;
        });
    }
}
