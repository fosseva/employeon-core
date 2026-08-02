<?php

declare(strict_types=1);

namespace Employeon\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'employeon:install {--force : Overwrite existing published assets and config}';

    protected $description = 'Publish Employeon frontend assets, config, and access-control migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'employeon-assets',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'employeon-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--provider' => 'Spatie\\Permission\\PermissionServiceProvider',
            '--tag' => 'permission-migrations',
        ]);

        $this->components->info('Employeon core installed. Run php artisan migrate to create access-control tables.');

        return self::SUCCESS;
    }
}
