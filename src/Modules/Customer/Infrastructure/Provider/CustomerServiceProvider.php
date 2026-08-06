<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Application\Command\AddAddress\AddAddressCommand;
use Modules\Customer\Application\Command\AddAddress\AddAddressHandler;
use Modules\Customer\Application\Command\MakeAddressDefault\MakeAddressDefaultCommand;
use Modules\Customer\Application\Command\MakeAddressDefault\MakeAddressDefaultHandler;
use Modules\Customer\Application\Command\RegisterCustomer\RegisterCustomerCommand;
use Modules\Customer\Application\Command\RegisterCustomer\RegisterCustomerHandler;
use Modules\Customer\Application\Command\RemoveAddress\RemoveAddressCommand;
use Modules\Customer\Application\Command\RemoveAddress\RemoveAddressHandler;
use Modules\Customer\Application\Command\UpdateAddress\UpdateAddressCommand;
use Modules\Customer\Application\Command\UpdateAddress\UpdateAddressHandler;
use Modules\Customer\Application\Command\UpdateCustomer\UpdateCustomerCommand;
use Modules\Customer\Application\Command\UpdateCustomer\UpdateCustomerHandler;
use Modules\Customer\Application\Port\Out\Identity\IAddressIdGenerator;
use Modules\Customer\Application\Port\Out\Identity\ICustomerIdGenerator;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Application\Query\GetCustomer\GetCustomerHandler;
use Modules\Customer\Application\Query\GetCustomer\GetCustomerQuery;
use Modules\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentityHandler;
use Modules\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentityQuery;
use Modules\Customer\Infrastructure\Adapter\Out\Identity\LaravelAddressIdGenerator;
use Modules\Customer\Infrastructure\Adapter\Out\Identity\LaravelCustomerIdGenerator;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentCustomerRepository;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ICustomerRepository::class, EloquentCustomerRepository::class);
        $this->app->bind(ICustomerIdGenerator::class, LaravelCustomerIdGenerator::class);
        $this->app->bind(IAddressIdGenerator::class, LaravelAddressIdGenerator::class);
    }

    public function boot(): void
    {
        $handlers = $this->app->make(HandlerRegistry::class);

        foreach ([
            AddAddressCommand::class => AddAddressHandler::class,
            MakeAddressDefaultCommand::class => MakeAddressDefaultHandler::class,
            RegisterCustomerCommand::class => RegisterCustomerHandler::class,
            RemoveAddressCommand::class => RemoveAddressHandler::class,
            UpdateAddressCommand::class => UpdateAddressHandler::class,
            UpdateCustomerCommand::class => UpdateCustomerHandler::class,
            GetCustomerQuery::class => GetCustomerHandler::class,
            GetCustomerByIdentityQuery::class => GetCustomerByIdentityHandler::class,
        ] as $messageClass => $handlerClass) {
            $handlers->register($messageClass, $handlerClass);
        }
    }
}
