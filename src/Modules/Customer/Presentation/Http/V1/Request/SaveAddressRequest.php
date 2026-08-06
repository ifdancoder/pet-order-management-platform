<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Request;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Customer\Application\Data\AddressData;

final class SaveAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:200'],
            'line_1' => ['required', 'string', 'max:200'],
            'line_2' => ['nullable', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'make_default' => ['sometimes', 'boolean'],
        ];
    }

    public function address(): AddressData
    {
        return new AddressData(
            recipientName: $this->string('recipient_name')->toString(),
            line1: $this->string('line_1')->toString(),
            line2: $this->nullableString('line_2'),
            city: $this->string('city')->toString(),
            region: $this->nullableString('region'),
            postalCode: $this->string('postal_code')->toString(),
            countryCode: $this->string('country_code')->toString(),
        );
    }

    public function makeDefault(): bool
    {
        return $this->boolean('make_default');
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : null;
    }
}
