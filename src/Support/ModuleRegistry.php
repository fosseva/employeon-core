<?php

declare(strict_types=1);

namespace Employeon\Support;

final class ModuleRegistry
{
    /**
     * @var array<string, Module>
     */
    private array $modules = [];

    public function register(Module $module): self
    {
        $this->modules[$module->path] = $module;

        return $this;
    }

    public function findByPath(string $path): ?Module
    {
        return $this->modules[$path] ?? null;
    }

    /**
     * @return array<int, array{label: string, href: string, icon: string, group: string}>
     */
    public function navigation(): array
    {
        return array_map(
            fn (Module $module): array => $module->navigation(),
            array_values($this->modules),
        );
    }
}
