<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\UserModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class UserModel extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'password_hash',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    protected static function newFactory(): UserModelFactory
    {
        return UserModelFactory::new();
    }
}
