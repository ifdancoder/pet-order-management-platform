<?php

declare(strict_types=1);

arch('return domain is independent of frameworks and outer layers')
    ->expect('Modules\Return\Domain')
    ->not->toUse([
        'Illuminate',
        'Modules\Return\Application',
        'Modules\Return\Infrastructure',
        'Modules\Return\Presentation',
    ]);

arch('return application is independent of infrastructure and presentation')
    ->expect('Modules\Return\Application')
    ->not->toUse([
        'Illuminate',
        'Modules\Return\Infrastructure',
        'Modules\Return\Presentation',
    ]);
