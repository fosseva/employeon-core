<?php

declare(strict_types=1);

namespace Employeon\Support;

use Employeon\Contracts\DatabaseConnectionResolver;
use Employeon\Support\Models\SavedView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final readonly class SavedViewManager
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    /**
     * @param  array<int, array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, sorts: array<int, array{column: string, direction: string}>, columns: array<int, string>, isDefault: bool}>  $defaultViews
     * @return array<int, array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, sorts: array<int, array{column: string, direction: string}>, columns: array<int, string>, isDefault: bool}>
     */
    public function views(?SavedViewOwner $owner, string $viewableType, array $defaultViews): array
    {
        $views = $defaultViews;

        if ($owner === null || ! $this->tableExists()) {
            return $views;
        }

        $rows = $this->query()
            ->where('owner_type', $owner->type)
            ->where('owner_id', $owner->id)
            ->where('viewable_type', $viewableType)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $view = $this->rowToView($row);
            $index = $this->defaultViewIndex($view['id'], $views);

            if ($index === null) {
                $views[] = $view;

                continue;
            }

            $views[$index] = [...$view, 'isDefault' => true];
        }

        return $views;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(?SavedViewOwner $owner, string $viewableType, string $key, array $data): void
    {
        if ($owner === null || ! $this->tableExists()) {
            return;
        }

        $payload = $this->payload($data);
        $existing = $this->query()
            ->where('owner_type', $owner->type)
            ->where('owner_id', $owner->id)
            ->where('viewable_type', $viewableType)
            ->where('key', $key)
            ->first();

        if ($existing instanceof SavedView) {
            $existing->fill($payload);
            $existing->save();

            return;
        }

        $view = $this->model();
        $view->fill($payload + [
            'owner_type' => $owner->type,
            'owner_id' => $owner->id,
            'viewable_type' => $viewableType,
            'key' => $key,
        ]);
        $view->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(?SavedViewOwner $owner, string $viewableType, array $data): void
    {
        $this->save($owner, $viewableType, (string) Str::uuid(), $data);
    }

    public function delete(?SavedViewOwner $owner, string $viewableType, string $key): void
    {
        if ($owner === null || ! $this->tableExists()) {
            return;
        }

        $this->query()
            ->where('owner_type', $owner->type)
            ->where('owner_id', $owner->id)
            ->where('viewable_type', $viewableType)
            ->where('key', $key)
            ->delete();
    }

    /**
     * @param  array<int, array{id: string}>  $views
     */
    private function defaultViewIndex(string $key, array $views): ?int
    {
        foreach ($views as $index => $view) {
            if ($view['id'] === $key) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, sorts: array<int, array{column: string, direction: string}>, columns: array<int, string>, isDefault: bool}
     */
    private function rowToView(object $row): array
    {
        $sortColumn = $this->stringValue($row->sort_column ?? null, 'name');
        $sortDirection = $this->stringValue($row->sort_direction ?? null, 'asc');

        return [
            'id' => $this->stringValue($row->key ?? null, 'all'),
            'name' => $this->stringValue($row->name ?? null, 'View'),
            'icon' => $this->stringValue($row->icon ?? null, 'eye'),
            'filterQuery' => $this->stringValue($row->filter_query ?? null),
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'sorts' => $this->sortList($row->sorts ?? null, [['column' => $sortColumn, 'direction' => $sortDirection]]),
            'columns' => $this->stringList($row->columns ?? null, ['id']),
            'isDefault' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $sorts = $this->sortList(
            $data['sorts'] ?? null,
            [[
                'column' => $this->stringValue($data['sortColumn'] ?? null, 'name'),
                'direction' => $this->stringValue($data['sortDirection'] ?? null, 'asc'),
            ]],
        );
        $primarySort = $sorts[0];

        return [
            'name' => $this->stringValue($data['name'] ?? null, 'View'),
            'icon' => $this->stringValue($data['icon'] ?? null, 'eye'),
            'filter_query' => $this->stringValue($data['filterQuery'] ?? null),
            'sort_column' => $primarySort['column'],
            'sort_direction' => $primarySort['direction'],
            'sorts' => $sorts,
            'columns' => $this->stringList($data['columns'] ?? null, ['id']),
        ];
    }

    /**
     * @return Builder<SavedView>
     */
    private function query(): Builder
    {
        return $this->model()->newQuery();
    }

    private function model(): SavedView
    {
        $model = new SavedView;
        $model->setTable($this->tableName());

        $connection = $this->connectionName();

        if ($connection !== null) {
            $model->setConnection($connection);
        }

        return $model;
    }

    private function tableExists(): bool
    {
        $connection = $this->connectionName();

        if ($connection !== null && ! is_array(config('database.connections.'.$connection))) {
            return false;
        }

        return Schema::connection($connection)->hasTable($this->tableName());
    }

    private function tableName(): string
    {
        return $this->configString('employeon.saved_views.table', 'saved_views');
    }

    private function connectionName(): ?string
    {
        return $this->databaseConnectionResolver->resolve();
    }

    private function configString(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function stringValue(mixed $value, string $default = ''): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private function stringList(mixed $value, array $default): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = $decoded;
        }

        if (! is_array($value)) {
            return $default;
        }

        $strings = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }

        return $strings !== [] ? array_values(array_unique($strings)) : $default;
    }

    /**
     * @param  array<int, array{column: string, direction: string}>  $default
     * @return array<int, array{column: string, direction: string}>
     */
    private function sortList(mixed $value, array $default): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = $decoded;
        }

        if (! is_array($value)) {
            return $default;
        }

        $sorts = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $column = $this->stringValue($item['column'] ?? null);
            $direction = $this->stringValue($item['direction'] ?? null, 'asc');

            if ($column === '') {
                continue;
            }

            $sorts[] = [
                'column' => $column,
                'direction' => $direction === 'desc' ? 'desc' : 'asc',
            ];
        }

        return $sorts !== [] ? $sorts : $default;
    }
}
