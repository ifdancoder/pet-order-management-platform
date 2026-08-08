<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\V1\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Payment\Domain\Enum\PaymentProvider;

final class RequestPaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(PaymentProvider::class)],
            'payment_method_reference' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:128'],
        ];
    }

    public function provider(): PaymentProvider
    {
        return PaymentProvider::from($this->string('provider')->toString());
    }

    public function idempotencyKey(): string
    {
        return $this->string('idempotency_key')->toString();
    }

    public function paymentMethodReference(): string
    {
        return $this->string('payment_method_reference')->toString();
    }
}
