<?php

declare(strict_types=1);

namespace Employeon;

use Employeon\Commands\InstallCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Inertia\Directive;
use Inertia\Inertia;

class EmployeonServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'employeon');

        Blade::directive('inertia', [Directive::class, 'compile']);
        Blade::directive('inertiaHead', [Directive::class, 'compileHead']);

        Inertia::setRootView('employeon::app');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../public/build' => public_path('vendor/employeon-core/build'),
            ], 'employeon-assets');
        }
    }
}
