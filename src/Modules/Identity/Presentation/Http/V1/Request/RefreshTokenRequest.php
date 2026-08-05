<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\V1\Request;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'max:512'],
        ];
    }

    public function refreshToken(): string
    {
        return $this->string('refresh_token')->toString();
    }
}
