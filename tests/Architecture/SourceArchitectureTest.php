<?php

declare(strict_types=1);

arch('source code stays inside the Employeon namespace')
    ->expect('Employeon')
    ->toUseStrictTypes()
    ->ignoring('Employeon\Tests');

arch('source code only depends on the minimal Laravel support layer')
    ->expect('Employeon')
    ->toOnlyUse([
        'Employeon',
        'Illuminate\Console',
        'Illuminate\Support',
        'Inertia',
        'public_path',
    ])
    ->ignoring('Employeon\Tests');

arch('source code does not use debugging helpers')
    ->expect('Employeon')
    ->not->toUse(['dd', 'dump', 'ray']);
