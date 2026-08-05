<?php

declare(strict_types=1);

use Modules\Identity\Domain\Exception\InvalidEmail;
use Modules\Identity\Domain\ValueObject\Email;

it('normalizes a valid email address', function () {
    $email = new Email('  USER@Example.COM ');

    expect($email->value())->toBe('user@example.com');
});

it('rejects an invalid email address', function () {
    new Email('not-an-email');
})->throws(InvalidEmail::class);
