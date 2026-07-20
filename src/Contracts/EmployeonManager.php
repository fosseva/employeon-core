<?php

namespace Employeon\Contracts;

use Employeon\Support\Module;

interface EmployeonManager
{
    public function module(Module $module): static;

    /**
     * @return array<string, Module>
     */
    public function modules(): array;

    public function resource(string $handle, string $model): static;

    /**
     * @return array<string, class-string>
     */
    public function resources(): array;

    public function route(string $group, string $path): static;

    /**
     * @return array<string, list<string>>
     */
    public function routes(): array;
}
