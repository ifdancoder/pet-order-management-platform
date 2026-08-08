<?php

declare(strict_types=1);

namespace Shared\Application\Event;

interface IIntegrationEvent
{
    public function name(): string;

    public function aggregateId(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
