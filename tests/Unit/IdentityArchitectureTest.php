<?php

declare(strict_types=1);

arch('identity domain has no framework or outer-layer dependencies')
    ->expect('Modules\Identity\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Identity\Application',
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation',
    ]);

arch('identity application has no framework or adapter dependencies')
    ->expect('Modules\Identity\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation',
    ]);

arch('identity presentation does not depend on infrastructure')
    ->expect('Modules\Identity\Presentation')
    ->not->toUse('Modules\Identity\Infrastructure');

arch('identity application ports use the interface prefix')
    ->expect('Modules\Identity\Application\Port')
    ->interfaces()
    ->toHavePrefix('I');
