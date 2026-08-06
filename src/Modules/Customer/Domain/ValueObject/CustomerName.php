<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\ValueObject;

use InvalidArgumentException;

final readonly class CustomerName
{
    public function __construct(
        private string $givenName,
        private string $familyName,
    ) {
        $this->assertValidPart($givenName, 'given');
        $this->assertValidPart($familyName, 'family');
    }

    public function givenName(): string
    {
        return $this->givenName;
    }

    public function familyName(): string
    {
        return $this->familyName;
    }

    private function assertValidPart(string $value, string $part): void
    {
        if (trim($value) === '' || mb_strlen($value) > 100) {
            throw new InvalidArgumentException(sprintf(
                'Customer %s name must contain between 1 and 100 characters.',
                $part,
            ));
        }
    }
}
