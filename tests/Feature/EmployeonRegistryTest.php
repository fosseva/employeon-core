<?php

use Employeon\Facades\Employeon;
use Employeon\Support\Module;

it('registers modules', function (): void {
    Employeon::module(new Module('employees', 'Employees'));

    expect(Employeon::modules())
        ->toHaveKey('employees')
        ->and(Employeon::modules()['employees']->name)->toBe('Employees');
});

it('registers resources', function (): void {
    Employeon::resource('employees', stdClass::class);

    expect(Employeon::resources())->toBe([
        'employees' => stdClass::class,
    ]);
});

it('registers route files by group', function (): void {
    Employeon::route('web', __DIR__.'/routes/web.php');

    expect(Employeon::routes())->toBe([
        'web' => [__DIR__.'/routes/web.php'],
    ]);
});
