<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\UserModel;

/**
 * @extends Factory<UserModel>
 */
final class UserModelFactory extends Factory
{
    protected $model = UserModel::class;

    protected static ?string $passwordHash;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid7()->toString(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => self::$passwordHash ??= Hash::make('password'),
            'status' => UserStatus::Active->value,
            'remember_token' => Str::random(10),
        ];
    }
}
