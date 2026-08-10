<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Customer\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CustomerModel;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;
use Modules\Order\Domain\Enum\OrderStatus;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderItemModel;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\OrderModel;
use Modules\Return\Domain\Enum\ReturnStatus;
use Tests\Fakes\Identity\FakeAccessTokenService;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->app->instance(IAccessTokenService::class, new FakeAccessTokenService);
});

it('creates and returns a return request for the authenticated customer', function (): void {
    [$user, $order, $item] = returnHttpRecords();

    $response = $this->withToken('access-token-'.$user->getKey())
        ->postJson(route('returns.store'), [
            'order_id' => $order->getKey(),
            'reason' => 'The product arrived damaged.',
            'items' => [[
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => 1,
            ]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.order_id', $order->getKey())
        ->assertJsonPath('data.status', ReturnStatus::Requested->value)
        ->assertJsonPath('data.refund_amount', 2500)
        ->assertJsonPath('data.currency', 'USD');

    $returnId = $response->json('data.id');

    $this->assertDatabaseHas('return_requests', [
        'id' => $returnId,
        'order_id' => $order->getKey(),
        'refund_amount' => 2500,
    ]);
    $this->assertDatabaseHas('return_items', [
        'return_request_id' => $returnId,
        'inventory_item_id' => $item->inventory_item_id,
        'quantity' => 1,
    ]);

    $this->withToken('access-token-'.$user->getKey())
        ->getJson(route('returns.show', ['returnId' => $returnId]))
        ->assertOk()
        ->assertJsonPath('data.id', $returnId)
        ->assertJsonCount(1, 'data.items');
});

it('rejects a return for an order that is not completed', function (): void {
    [$user, $order, $item] = returnHttpRecords(OrderStatus::Shipped);

    $this->withToken('access-token-'.$user->getKey())
        ->postJson(route('returns.store'), [
            'order_id' => $order->getKey(),
            'reason' => 'Changed my mind.',
            'items' => [[
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => 1,
            ]],
        ])
        ->assertUnprocessable()
        ->assertExactJson([
            'error' => [
                'code' => 'return_not_eligible',
                'message' => 'Only completed orders can be returned.',
            ],
        ]);

    $this->assertDatabaseCount('return_requests', 0);
});

it('does not expose another customer order or return', function (): void {
    [$user, $order, $item] = returnHttpRecords();
    $returnId = $this->withToken('access-token-'.$user->getKey())
        ->postJson(route('returns.store'), [
            'order_id' => $order->getKey(),
            'reason' => 'The product arrived damaged.',
            'items' => [[
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => 1,
            ]],
        ])
        ->assertCreated()
        ->json('data.id');
    $anotherUser = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
    CustomerModel::factory()->create([
        'identity_user_id' => $anotherUser->getKey(),
    ]);

    $this->withToken('access-token-'.$anotherUser->getKey())
        ->getJson(route('returns.show', ['returnId' => $returnId]))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'return_not_found');
});

it('rejects duplicate return requests for the same order', function (): void {
    [$user, $order, $item] = returnHttpRecords();
    $request = fn () => $this->withToken('access-token-'.$user->getKey())
        ->postJson(route('returns.store'), [
            'order_id' => $order->getKey(),
            'reason' => 'The product arrived damaged.',
            'items' => [[
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => 1,
            ]],
        ]);

    $request()->assertCreated();
    $request()
        ->assertConflict()
        ->assertJsonPath('error.code', 'return_already_exists');

    $this->assertDatabaseCount('return_requests', 1);
});

/** @return array{UserModel, OrderModel, OrderItemModel} */
function returnHttpRecords(
    OrderStatus $status = OrderStatus::Completed,
): array {
    $user = UserModel::factory()->create([
        'status' => UserStatus::Active->value,
    ]);
    $customer = CustomerModel::factory()->create([
        'identity_user_id' => $user->getKey(),
    ]);
    $order = OrderModel::factory()->create([
        'customer_id' => $customer->getKey(),
        'status' => $status->value,
        'currency' => 'USD',
        'total_amount' => 5000,
        'updated_at' => now()->subDays(5),
    ]);
    $item = OrderItemModel::factory()->create([
        'order_id' => $order->getKey(),
        'quantity' => 2,
        'unit_price_amount' => 2500,
    ]);

    return [$user, $order, $item];
}
