<?php

declare(strict_types=1);

use Modules\Notification\Infrastructure\Mail\WelcomeUserMail;

it('renders the welcome message in html and plain text', function (): void {
    $mail = new WelcomeUserMail(
        '018f22e2-7c2a-7a33-8c4c-4ea690ad4fd2',
    );

    $mail->assertSeeInHtml('Welcome to OrderFlow')
        ->assertSeeInHtml('awaiting activation')
        ->assertSeeInText('Welcome to OrderFlow')
        ->assertSeeInText('awaiting activation');
});
