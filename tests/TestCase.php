<?php

declare(strict_types=1);

namespace Employeon\Tests;

use Employeon\EmployeonServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EmployeonServiceProvider::class,
        ];
    }
}
