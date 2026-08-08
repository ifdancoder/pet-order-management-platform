<?php

declare(strict_types=1);

return [
    'fake' => [
        'decline' => (bool) env('PAYMENT_FAKE_DECLINE', false),
    ],
];
