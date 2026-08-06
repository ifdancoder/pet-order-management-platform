<?php

declare(strict_types=1);

namespace Modules\Promotion\Infrastructure\Adapter\Out\Persistence\Eloquent\Model;

use Database\Factories\PromotionModelFactory;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class PromotionModel extends Model
{
    /** @use HasFactory<PromotionModelFactory> */
    use HasFactory;

    protected $table = 'promotions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'enabled',
        'discount_type',
        'discount_value',
        'currency',
        'minimum_order_amount',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'discount_value' => 'integer',
            'minimum_order_amount' => 'integer',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->dateTimeAttribute('starts_at');
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->dateTimeAttribute('ends_at');
    }

    protected static function newFactory(): PromotionModelFactory
    {
        return PromotionModelFactory::new();
    }

    private function dateTimeAttribute(string $name): DateTimeImmutable
    {
        $value = $this->getAttribute($name);

        if (! $value instanceof DateTimeInterface) {
            throw new LogicException(sprintf(
                'Promotion attribute "%s" must be a date-time value.',
                $name,
            ));
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
