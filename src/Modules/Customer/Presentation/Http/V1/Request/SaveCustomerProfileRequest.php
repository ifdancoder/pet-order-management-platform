<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\V1\Request;

use Illuminate\Foundation\Http\FormRequest;

final class SaveCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'given_name' => ['required', 'string', 'max:100'],
            'family_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        ];
    }

    public function givenName(): string
    {
        return $this->string('given_name')->toString();
    }

    public function familyName(): string
    {
        return $this->string('family_name')->toString();
    }

    public function phoneNumber(): ?string
    {
        $phoneNumber = $this->validated('phone_number');

        return is_string($phoneNumber) ? $phoneNumber : null;
    }
}
