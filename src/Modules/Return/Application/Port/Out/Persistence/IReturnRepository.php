<?php

declare(strict_types=1);

namespace Modules\Return\Application\Port\Out\Persistence;

use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\ValueObject\ReturnId;

interface IReturnRepository
{
    public function claim(ReturnRequest $return): bool;

    public function save(ReturnRequest $return): void;

    public function findById(ReturnId $returnId): ?ReturnRequest;

    public function findByIdForUpdate(ReturnId $returnId): ?ReturnRequest;
}
