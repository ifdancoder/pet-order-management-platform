<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Payment\Domain\Enum\PaymentProvider;
use Modules\Payment\Domain\Enum\PaymentStatus;
use Modules\Payment\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\PaymentModel;

/** @extends Factory<PaymentModel> */
final class PaymentModelFactory extends Factory
{
    protected $model = PaymentModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'order_id' => Str::uuid7()->toString(),
            'amount' => 2500,
            'currency' => 'USD',
            'provider' => PaymentProvider::Fake->value,
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => Str::uuid()->toString(),
            'request_hash' => hash('sha256', fake()->uuid()),
            'provider_payment_id' => null,
            'failure_code' => null,
        ];
    }
}
