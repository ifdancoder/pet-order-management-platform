<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Command;

use Closure;
use Psr\Log\LoggerInterface;
use Shared\Application\Bus\Command\ICommand;
use Shared\Application\Bus\Command\ICommandMiddleware;
use Throwable;

final readonly class LoggingCommandMiddleware implements ICommandMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * @param  ICommand<mixed>  $command
     * @param  Closure(ICommand<mixed>): mixed  $next
     */
    public function process(ICommand $command, Closure $next): mixed
    {
        $context = ['command' => $command::class];

        $this->logger->info('Command started.', $context);

        try {
            $result = $next($command);
        } catch (Throwable $exception) {
            $this->logger->error('Command failed.', [
                ...$context,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }

        $this->logger->info('Command completed.', $context);

        return $result;
    }
}
