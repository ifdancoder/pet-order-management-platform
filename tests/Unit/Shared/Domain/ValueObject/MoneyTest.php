<?php

declare(strict_types=1);

use Shared\Domain\Exception\CurrencyMismatch;
use Shared\Domain\ValueObject\Money;

it('adds and multiplies minor monetary units', function () {
    $money = new Money(1250, 'USD');

    $total = $money->multiply(2)->add(new Money(500, 'USD'));

    expect($total->amount())->toBe(3000)
        ->and($total->currency())->toBe('USD');
});

it('rejects arithmetic across currencies', function () {
    new Money(100, 'USD')->add(new Money(100, 'EUR'));
})->throws(CurrencyMismatch::class);

it('rejects negative amounts', function () {
    new Money(-1, 'USD');
})->throws(InvalidArgumentException::class);

it('subtracts and selects the smaller amount', function () {
    $money = new Money(1250, 'USD');

    $remainder = $money->subtract(new Money(250, 'USD'));

    expect($remainder->amount())->toBe(1000)
        ->and($money->minimum(new Money(500, 'USD'))->amount())->toBe(500);
});

it('rejects subtraction below zero', function () {
    new Money(100, 'USD')->subtract(new Money(101, 'USD'));
})->throws(InvalidArgumentException::class);
