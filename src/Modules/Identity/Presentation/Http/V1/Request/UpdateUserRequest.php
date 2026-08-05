<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required_without:password',
                'string',
                'email:rfc',
                'max:254',
            ],
            'password' => [
                'required_without:email',
                'string',
                'max:72',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ];
    }

    public function email(): ?string
    {
        return $this->has('email')
            ? $this->string('email')->toString()
            : null;
    }

    public function password(): ?string
    {
        return $this->has('password')
            ? $this->string('password')->toString()
            : null;
    }
}
