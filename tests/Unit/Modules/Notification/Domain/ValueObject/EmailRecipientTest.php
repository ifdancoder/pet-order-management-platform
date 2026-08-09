<?php

declare(strict_types=1);

use Modules\Notification\Domain\Exception\InvalidRecipient;
use Modules\Notification\Domain\ValueObject\EmailRecipient;

it('normalizes a valid email recipient', function (): void {
    $recipient = new EmailRecipient(' User@Example.COM ');

    expect($recipient->value())->toBe('user@example.com');
});

it('rejects an invalid email recipient', function (): void {
    expect(fn () => new EmailRecipient('not-an-email'))
        ->toThrow(InvalidRecipient::class);
});
