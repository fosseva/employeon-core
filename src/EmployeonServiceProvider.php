<?php

declare(strict_types=1);

namespace Employeon;

use Employeon\Commands\InstallCommand;
use Employeon\Contracts\DatabaseConnectionResolver;
use Employeon\Support\DefaultDatabaseConnectionResolver;
use Employeon\Support\Module;
use Employeon\Support\ModuleRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Inertia\Directive;
use Inertia\Inertia;

class EmployeonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/employeon.php', 'employeon');

        $this->app->singleton(DatabaseConnectionResolver::class, function (): DatabaseConnectionResolver {
            $resolver = config('employeon.database_resolver', DefaultDatabaseConnectionResolver::class);

            if (is_string($resolver) && class_exists($resolver)) {
                $instance = $this->app->make($resolver);

                if ($instance instanceof DatabaseConnectionResolver) {
                    return $instance;
                }
            }

            return new DefaultDatabaseConnectionResolver;
        });

        $this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
            $registry = new ModuleRegistry;

            foreach ($this->defaultModules() as $module) {
                $registry->register($module);
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'employeon');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Blade::directive('inertia', [Directive::class, 'compile']);
        Blade::directive('inertiaHead', [Directive::class, 'compileHead']);

        Inertia::setRootView('employeon::app');
        Inertia::share('employeon.navigation', fn (): array => $this->app->make(ModuleRegistry::class)->navigation());

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../public/build' => public_path('vendor/employeon-core/build'),
            ], 'employeon-assets');

            $this->publishes([
                __DIR__.'/../config/employeon.php' => config_path('employeon.php'),
            ], 'employeon-config');
        }
    }

    /**
     * @return array<int, Module>
     */
    private function defaultModules(): array
    {
        $module = fn (string $path, string $label, string $group, string $icon, string $subtitle): Module => Module::make($path)
            ->label($label)
            ->group($group)
            ->icon($icon)
            ->title($label)
            ->subtitle($subtitle);

        return [
            $module('dashboard', 'Dashboard', 'Workforce', 'gauge', 'Workforce command center'),
            Module::make('employees')
                ->label('Employees')
                ->group('Workforce')
                ->icon('users')
                ->title('Employees')
                ->subtitle('Employee directory and records')
                ->component('Employees/Index'),
            $module('departments', 'Departments', 'Workforce', 'building', 'Team structure and reporting lines'),
            Module::make('attendance')
                ->label('Attendance')
                ->group('Operations')
                ->icon('calendar')
                ->title('Attendance')
                ->subtitle('Daily presence and time tracking')
                ->component('Attendance/Index'),
            $module('leaves', 'Leaves', 'Operations', 'clipboard', 'Leave requests and approvals'),
            $module('payroll', 'Payroll', 'Operations', 'dollar', 'Payroll cycles and compensation reviews'),
            $module('compliance', 'Compliance', 'Operations', 'shield', 'Policy, document, and audit readiness'),
            $module('reports', 'Reports', 'Admin', 'clipboard', 'Operational reports and export templates'),
            Module::make('roles-permissions')
                ->label('Roles & Permissions')
                ->group('Admin')
                ->icon('key')
                ->title('Roles & Permissions')
                ->subtitle('Role templates and permission access matrix')
                ->component('RolesPermissions/Index'),
            $module('settings', 'Settings', 'Admin', 'settings', 'Workspace preferences and system controls'),
            $module('support', 'Support', 'Admin', 'life-buoy', 'Help desk and implementation guidance'),
        ];
    }
}
