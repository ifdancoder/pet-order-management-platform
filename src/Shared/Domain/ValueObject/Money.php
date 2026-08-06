<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Exception\CurrencyMismatch;

final readonly class Money
{
    public function __construct(
        private int $amount,
        private string $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException(
                'Currency must be an ISO 4217 alpha-3 code.',
            );
        }
    }

    public static function zero(string $currency): self
    {
        return new self(0, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->guardSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException(
                'Money multiplier cannot be negative.',
            );
        }

        return new self($this->amount * $multiplier, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    private function guardSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatch::between(
                $this->currency,
                $other->currency,
            );
        }
    }
}
