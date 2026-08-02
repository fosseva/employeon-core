<?php

declare(strict_types=1);

namespace Employeon\Support;

use Employeon\Contracts\DatabaseConnectionResolver;

final readonly class DefaultDatabaseConnectionResolver implements DatabaseConnectionResolver
{
    public function resolve(): ?string
    {
        return null;
    }
}
