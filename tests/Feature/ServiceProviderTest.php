<?php

declare(strict_types=1);

use Employeon\EmployeonServiceProvider;

it('registers the Employeon service provider', function (): void {
    expect($this->app->getProvider(EmployeonServiceProvider::class))
        ->toBeInstanceOf(EmployeonServiceProvider::class);
});
