<?php

declare(strict_types=1);

namespace Shared\Application\Messaging;

use LogicException;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Application\Port\Out\Messaging\IProcessedMessageStore;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final class IntegrationMessageConsumer
{
    /** @var array<string, IIntegrationMessageHandler> */
    private array $handlers = [];

    /** @param iterable<IIntegrationMessageHandler> $handlers */
    public function __construct(
        iterable $handlers,
        private readonly IProcessedMessageStore $processedMessages,
        private readonly ITransactionManager $transaction,
    ) {
        foreach ($handlers as $handler) {
            foreach ($handler->messageNames() as $messageName) {
                if (isset($this->handlers[$messageName])) {
                    throw new LogicException(sprintf(
                        'Integration message "%s" has multiple handlers.',
                        $messageName,
                    ));
                }

                $this->handlers[$messageName] = $handler;
            }
        }
    }

    public function consume(IntegrationMessage $message): bool
    {
        $handler = $this->handlers[$message->name]
            ?? throw new LogicException(sprintf(
                'No handler is registered for integration message "%s".',
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
