<?php

declare(strict_types=1);

namespace Modules\Return\Presentation\Http\V1\Request;

use Illuminate\Foundation\Http\FormRequest;

final class RequestReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function orderId(): string
    {
        return $this->string('order_id')->toString();
    }

    public function reason(): string
    {
        return $this->string('reason')->toString();
    }

    /** @return list<array{inventory_item_id: string, quantity: int}> */
    public function items(): array
    {
        $items = $this->validated('items');

        return array_values(array_map(
            static fn (array $item): array => [
                'inventory_item_id' => (string) $item['inventory_item_id'],
                'quantity' => (int) $item['quantity'],
            ],
            is_array($items) ? $items : [],
        ));
    }
}
