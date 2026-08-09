<?php

declare(strict_types=1);

namespace Shared\Application\Port\In\Messaging;

use Shared\Application\Messaging\IntegrationMessage;

interface IIntegrationMessageHandler
{
    public function consumerName(): string;

    /** @return list<string> */
    public function messageNames(): array;

    public function handle(IntegrationMessage $message): void;
}
