<?php

declare(strict_types=1);

use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\PhoneNumber;
use Modules\Customer\Domain\ValueObject\PostalAddress;

it('rejects a blank customer name', function () {
    new CustomerName(' ', 'Lovelace');
})->throws(InvalidArgumentException::class);

it('requires phone numbers in e164 format', function () {
    new PhoneNumber('202-555-0123');
})->throws(InvalidArgumentException::class);

it('requires an uppercase alpha two country code', function () {
    new PostalAddress(
        recipientName: 'Ada Lovelace',
        line1: '1 Analytical Engine Road',
        line2: null,
        city: 'London',
        region: null,
        postalCode: 'SW1A 1AA',
        countryCode: 'gb',
    );
})->throws(InvalidArgumentException::class);
