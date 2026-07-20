<?php

namespace Employeon;

use Employeon\Contracts\EmployeonManager;
use Employeon\Support\Module;
use InvalidArgumentException;

class Employeon implements EmployeonManager
{
    /**
     * @var array<string, Module>
     */
    protected array $modules = [];

    /**
     * @var array<string, class-string>
     */
    protected array $resources = [];

    /**
     * @var array<string, list<string>>
     */
    protected array $routes = [];

    public function module(Module $module): static
    {
        $this->modules[$module->handle] = $module;

        return $this;
    }

    public function modules(): array
    {
        return $this->modules;
    }

    public function resource(string $handle, string $model): static
    {
        if ($handle === '') {
            throw new InvalidArgumentException('Employeon resource handles cannot be empty.');
        }

        if (! class_exists($model)) {
            throw new InvalidArgumentException("Employeon resource model [{$model}] does not exist.");
        }

        $this->resources[$handle] = $model;

        return $this;
    }

    public function resources(): array
    {
        return $this->resources;
    }

    public function route(string $group, string $path): static
    {
        if ($group === '') {
            throw new InvalidArgumentException('Employeon route groups cannot be empty.');
        }

        $this->routes[$group] ??= [];
        $this->routes[$group][] = $path;

        return $this;
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
