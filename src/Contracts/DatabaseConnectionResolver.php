<?php

declare(strict_types=1);

namespace Employeon\Contracts;

interface DatabaseConnectionResolver
{
    public function resolve(): ?string;
}
