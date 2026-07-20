<?php

namespace Employeon;

use Employeon\Contracts\EmployeonManager;
use Illuminate\Support\ServiceProvider;

class EmployeonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Employeon::class, fn (): Employeon => new Employeon);
        $this->app->alias(Employeon::class, EmployeonManager::class);
    }
}
