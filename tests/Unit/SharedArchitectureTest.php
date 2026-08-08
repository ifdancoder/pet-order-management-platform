<?php

declare(strict_types=1);

arch('shared application has no framework or infrastructure dependencies')
    ->expect('Shared\Application')
    ->not->toUse([
        'Illuminate',
        'Shared\Infrastructure',
        'Shared\Presentation',
    ]);

arch('shared application ports use the interface prefix')
    ->expect('Shared\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');
