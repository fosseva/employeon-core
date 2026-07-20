<?php

declare(strict_types=1);

namespace Employeon\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'employeon:install {--force : Overwrite existing published assets}';

    protected $description = 'Publish Employeon frontend assets.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'employeon-assets',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->components->info('Employeon assets installed.');

        return self::SUCCESS;
    }
}
