<?php

declare(strict_types=1);

namespace Shared\Application\Port\Out\Outbox;

use Shared\Application\Event\IIntegrationEvent;

interface IOutboxWriter
{
    public function record(IIntegrationEvent $event): void;
}
