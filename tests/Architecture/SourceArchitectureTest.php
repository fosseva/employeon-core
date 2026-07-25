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
        'Illuminate\Bus',
        'Illuminate\Console',
        'Illuminate\Contracts',
        'Illuminate\Database',
        'Illuminate\Http',
        'Illuminate\Notifications',
        'Illuminate\Support',
        'Inertia',
        'app',
        'back',
        'event',
        'Spatie\Permission',
        'config',
        'config_path',
        'now',
        'public_path',
        'redirect',
    ])
    ->ignoring('Employeon\Tests');

arch('source code does not use debugging helpers')
    ->expect('Employeon')
    ->not->toUse(['dd', 'dump', 'ray']);
