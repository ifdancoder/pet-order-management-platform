<?php

declare(strict_types=1);

arch('source classes use strict types')
    ->expect(['Modules', 'Shared'])
    ->toUseStrictTypes();

arch('shared code does not depend on business modules')
    ->expect('Shared')
    ->not->toUse('Modules');
