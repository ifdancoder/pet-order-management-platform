<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entity;

use InvalidArgumentException;
use Modules\Inventory\Domain\Enum\ReservationStatus;
use Modules\Inventory\Domain\Exception\DuplicateReservationItem;
use Modules\Inventory\Domain\ValueObject\ReservationId;
use Modules\Inventory\Domain\ValueObject\ReservationKey;
use Modules\Inventory\Domain\ValueObject\ReservationLine;

final class Reservation
{
    /** @var list<ReservationLine> */
    private array $lines;

    /**
     * @param  list<ReservationLine>  $lines
     */
    public function __construct(
        private readonly ReservationId $id,
        private readonly ReservationKey $key,
        array $lines,
        private ReservationStatus $status,
    ) {
        if ($lines === []) {
            throw new InvalidArgumentException(
                'A reservation must contain at least one item.',
            );
        }

        $itemIds = [];

        foreach ($lines as $line) {
            $itemId = $line->inventoryItemId()->value();

            if (isset($itemIds[$itemId])) {
                throw DuplicateReservationItem::withId(
                    $line->inventoryItemId(),
                );
            }

            $itemIds[$itemId] = true;
        }

        $this->lines = $lines;
    }

    /**
     * @param  list<ReservationLine>  $lines
     */
    public static function create(
        ReservationId $id,
        ReservationKey $key,
        array $lines,
    ): self {
        return new self($id, $key, $lines, ReservationStatus::Active);
    }

    public function id(): ReservationId
    {
        return $this->id;
    }

    public function key(): ReservationKey
    {
        return $this->key;
    }

    /** @return list<ReservationLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function release(): bool
    {
        if ($this->status === ReservationStatus::Released) {
            return false;
        }

        $this->status = ReservationStatus::Released;

        return true;
    }
}
