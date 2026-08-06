<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property DateTimeImmutable $expires_at
 * @property DateTimeImmutable|null $consumed_at
 * @property DateTimeImmutable|null $revoked_at
 */
final class RefreshTokenModel extends Model
{
    protected $table = 'identity_refresh_tokens';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'family_id',
        'user_id',
        'token_hash',
        'expires_at',
        'consumed_at',
        'revoked_at',
        'created_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
