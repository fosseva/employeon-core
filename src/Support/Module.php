<?php

declare(strict_types=1);

namespace Employeon\Support;

use Closure;
use Illuminate\Http\Request;

final class Module
{
    /**
     * @param  array<int, array{label: string, value: string}>  $stats
     * @param  array<int, array<string, string>>  $rows
     * @param  Closure(Request): array<string, mixed>|null  $props
     */
    public function __construct(
        public string $slug,
        public string $label,
        public string $path,
        public string $group,
        public string $icon,
        public string $title,
        public string $subtitle,
        public string $component = 'ModuleTemplate',
        public array $stats = [],
        public array $rows = [],
        public ?Closure $props = null,
    ) {}

    public static function make(string $slug): self
    {
        $label = ucwords(str_replace(['-', '_'], ' ', $slug));

        return new self(
            slug: $slug,
            label: $label,
            path: $slug,
            group: 'Admin',
            icon: 'clipboard',
            title: $label,
            subtitle: '',
        );
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function path(string $path): self
    {
        $this->path = trim($path, '/');

        return $this;
    }

    public function group(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function subtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function component(string $component): self
    {
        $this->component = $component;

        return $this;
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $stats
     */
    public function stats(array $stats): self
    {
        $this->stats = $stats;

        return $this;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function rows(array $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @param  Closure(Request): array<string, mixed>  $props
     */
    public function props(Closure $props): self
    {
        $this->props = $props;

        return $this;
    }

    /**
     * @return array{label: string, href: string, icon: string, group: string}
     */
    public function navigation(): array
    {
        return [
            'label' => $this->label,
            'href' => '/'.$this->path,
            'icon' => $this->icon,
            'group' => $this->group,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inertiaProps(Request $request): array
    {
        $props = [
            'template' => [
                'title' => $this->title,
                'subtitle' => $this->subtitle,
                'stats' => $this->stats,
                'rows' => $this->rows,
            ],
        ];

        if ($this->props !== null) {
            $resolvedProps = ($this->props)($request);

            foreach ($resolvedProps as $key => $value) {
                $props[$key] = $value;
            }
        }

        return $props;
    }
}
