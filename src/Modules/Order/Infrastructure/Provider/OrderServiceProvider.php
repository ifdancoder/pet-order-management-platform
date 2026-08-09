<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Order\Application\Checkout\CheckoutRulePipeline;
use Modules\Order\Application\Checkout\Rule\ApplyPromotionsRule;
use Modules\Order\Application\Checkout\Rule\CustomerCanOrderRule;
use Modules\Order\Application\Checkout\Rule\InventoryAvailableRule;
use Modules\Order\Application\Checkout\Rule\OrderNotEmptyRule;
use Modules\Order\Application\Command\AddOrderItem\AddOrderItemCommand;
use Modules\Order\Application\Command\AddOrderItem\AddOrderItemHandler;
use Modules\Order\Application\Command\Checkout\CheckoutCommand;
use Modules\Order\Application\Command\Checkout\CheckoutHandler;
use Modules\Order\Application\Command\CreateOrderDraft\CreateOrderDraftCommand;
use Modules\Order\Application\Command\CreateOrderDraft\CreateOrderDraftHandler;
use Modules\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use Modules\Order\Application\Command\PlaceOrder\PlaceOrderHandler;
use Modules\Order\Application\Command\RemoveOrderItem\RemoveOrderItemCommand;
use Modules\Order\Application\Command\RemoveOrderItem\RemoveOrderItemHandler;
use Modules\Order\Application\Command\TransitionOrder\TransitionOrderCommand;
use Modules\Order\Application\Command\TransitionOrder\TransitionOrderHandler;
use Modules\Order\Application\Messaging\PaymentStatusChangedHandler;
use Modules\Order\Application\Payment\OrderPayment;
use Modules\Order\Application\Port\In\IOrderPayment;
use Modules\Order\Application\Port\Out\Checkout\ICustomerCheckoutGateway;
use Modules\Order\Application\Port\Out\Checkout\IInventoryCheckoutGateway;
use Modules\Order\Application\Port\Out\Checkout\IPromotionCheckoutGateway;
use Modules\Order\Application\Port\Out\Identity\IOrderIdGenerator;
use Modules\Order\Application\Port\Out\Persistence\ICheckoutRepository;
use Modules\Order\Application\Port\Out\Persistence\IOrderRepository;
use Modules\Order\Application\Query\GetOrder\GetOrderHandler;
use Modules\Order\Application\Query\GetOrder\GetOrderQuery;
use Modules\Order\Infrastructure\Adapter\Out\Checkout\CustomerCheckoutGateway;
use Modules\Order\Infrastructure\Adapter\Out\Checkout\InventoryCheckoutGateway;
use Modules\Order\Infrastructure\Adapter\Out\Checkout\PromotionCheckoutGateway;
use Modules\Order\Infrastructure\Adapter\Out\Identity\LaravelOrderIdGenerator;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentCheckoutRepository;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentOrderRepository;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Infrastructure\Bus\HandlerRegistry;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IOrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(ICheckoutRepository::class, EloquentCheckoutRepository::class);
        $this->app->bind(IOrderIdGenerator::class, LaravelOrderIdGenerator::class);
        $this->app->bind(ICustomerCheckoutGateway::class, CustomerCheckoutGateway::class);
        $this->app->bind(IInventoryCheckoutGateway::class, InventoryCheckoutGateway::class);
        $this->app->bind(IPromotionCheckoutGateway::class, PromotionCheckoutGateway::class);
        $this->app->bind(IOrderPayment::class, OrderPayment::class);
        $this->app->tag(
            PaymentStatusChangedHandler::class,
            IIntegrationMessageHandler::class,
        );
        $this->app->tag([
            CustomerCanOrderRule::class,
            OrderNotEmptyRule::class,
            InventoryAvailableRule::class,
            ApplyPromotionsRule::class,
        ], 'order.checkout.rules');
        $this->app->singleton(
            CheckoutRulePipeline::class,
            fn (): CheckoutRulePipeline => new CheckoutRulePipeline(
                $this->app->tagged('order.checkout.rules'),
            ),
        );
    }

    public function boot(): void
    {
        $handlers = $this->app->make(HandlerRegistry::class);

        foreach ([
            CheckoutCommand::class => CheckoutHandler::class,
            AddOrderItemCommand::class => AddOrderItemHandler::class,
            CreateOrderDraftCommand::class => CreateOrderDraftHandler::class,
            PlaceOrderCommand::class => PlaceOrderHandler::class,
            RemoveOrderItemCommand::class => RemoveOrderItemHandler::class,
            TransitionOrderCommand::class => TransitionOrderHandler::class,
            GetOrderQuery::class => GetOrderHandler::class,
        ] as $messageClass => $handlerClass) {
            $handlers->register($messageClass, $handlerClass);
        }
    }
}
