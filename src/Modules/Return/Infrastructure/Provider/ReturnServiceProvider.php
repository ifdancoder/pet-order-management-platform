<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Provider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Return\Application\Command\ApproveReturn\ApproveReturnCommand;
use Modules\Return\Application\Command\ApproveReturn\ApproveReturnHandler;
use Modules\Return\Application\Command\ReceiveReturn\ReceiveReturnCommand;
use Modules\Return\Application\Command\ReceiveReturn\ReceiveReturnHandler;
use Modules\Return\Application\Command\RejectReturn\RejectReturnCommand;
use Modules\Return\Application\Command\RejectReturn\RejectReturnHandler;
use Modules\Return\Application\Command\RequestReturn\RequestReturnCommand;
use Modules\Return\Application\Command\RequestReturn\RequestReturnHandler;
use Modules\Return\Application\Messaging\PaymentRefundedReturnHandler;
use Modules\Return\Application\Port\Out\Customer\IReturnCustomerGateway;
use Modules\Return\Application\Port\Out\Identity\IReturnIdGenerator;
use Modules\Return\Application\Port\Out\Order\IReturnOrderGateway;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Application\Port\Out\Time\IReturnClock;
use Modules\Return\Application\Query\GetReturn\GetReturnHandler;
use Modules\Return\Application\Query\GetReturn\GetReturnQuery;
use Modules\Return\Domain\Service\ReturnEligibilityPolicy;
use Modules\Return\Domain\Specification\AndReturnEligibilitySpecification;
use Modules\Return\Domain\Specification\OrderCompletedSpecification;
use Modules\Return\Domain\Specification\PurchasedItemsSpecification;
use Modules\Return\Domain\Specification\WithinReturnWindowSpecification;
use Modules\Return\Infrastructure\Adapter\Out\Customer\CustomerIdentityGateway;
use Modules\Return\Infrastructure\Adapter\Out\Identity\LaravelReturnIdGenerator;
use Modules\Return\Infrastructure\Adapter\Out\Order\OrderReturnGateway;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentReturnRepository;
use Modules\Return\Infrastructure\Adapter\Out\Time\SystemReturnClock;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class ReturnServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IReturnIdGenerator::class, LaravelReturnIdGenerator::class);
        $this->app->bind(IReturnClock::class, SystemReturnClock::class);
        $this->app->bind(IReturnOrderGateway::class, OrderReturnGateway::class);
        $this->app->bind(IReturnCustomerGateway::class, CustomerIdentityGateway::class);
        $this->app->bind(IReturnRepository::class, EloquentReturnRepository::class);
        $this->app->singleton(ReturnEligibilityPolicy::class, function (): ReturnEligibilityPolicy {
            $windowDays = $this->app->make(ConfigRepository::class)->get('return.window_days');

            if (! is_int($windowDays) || $windowDays < 1) {
                throw new LogicException('Return window configuration must be positive.');
            }

            return new ReturnEligibilityPolicy(
                new AndReturnEligibilitySpecification([
                    new OrderCompletedSpecification,
                    new WithinReturnWindowSpecification($windowDays),
                    new PurchasedItemsSpecification,
                ]),
            );
        });
        $this->app->tag(PaymentRefundedReturnHandler::class, IIntegrationMessageHandler::class);
    }

    public function boot(): void
    {
        $handlers = $this->app->make(HandlerRegistry::class);

        foreach ([
            RequestReturnCommand::class => RequestReturnHandler::class,
            ApproveReturnCommand::class => ApproveReturnHandler::class,
            RejectReturnCommand::class => RejectReturnHandler::class,
            ReceiveReturnCommand::class => ReceiveReturnHandler::class,
            GetReturnQuery::class => GetReturnHandler::class,
        ] as $message => $handler) {
            $handlers->register($message, $handler);
        }

        RateLimiter::for(
            'return-request',
            fn (Request $request): Limit => Limit::perMinute(10)->by(
                $request->attributes->getString('identity.user_id').'|'.$request->ip(),
            ),
        );

        Route::middleware('api')
            ->prefix('api/v1/returns')
            ->name('returns.')
            ->group(dirname(__DIR__, 2).'/Presentation/Http/V1/Routes/return_routes.php');
    }
}
