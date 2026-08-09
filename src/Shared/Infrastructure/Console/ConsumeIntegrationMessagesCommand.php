<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Console;

use Illuminate\Console\Command;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;

final class ConsumeIntegrationMessagesCommand extends Command
{
    protected $signature = 'messaging:consume {consumer}';

    protected $description = 'Consume integration messages from RabbitMQ';

    public function __construct(
        private readonly RabbitMqMessageConsumer $messages,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $running = true;
        $this->trap([SIGINT, SIGTERM], static function () use (&$running): void {
            $running = false;
        });
        $consumer = $this->argument('consumer');

        $this->messages->consume($consumer, static fn (): bool => $running);

        return self::SUCCESS;
    }
}
