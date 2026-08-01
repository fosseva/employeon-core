<?php

declare(strict_types=1);

namespace Employeon\Employees;

use Employeon\Contracts\DatabaseConnectionResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final readonly class EmployeeViewManager
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    /**
     * @return array<int, array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, columns: array<int, string>, isDefault: bool}>
     */
    public function views(?string $ownerEmail): array
    {
        $views = $this->defaultViews();

        if (! $this->tableExists()) {
            return $views;
        }

        $rows = $this->table()
            ->where('owner_email', $ownerEmail)
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
    public function save(?string $ownerEmail, string $key, array $data): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $payload = $this->payload($data);
        $existingId = $this->table()
            ->where('owner_email', $ownerEmail)
            ->where('key', $key)
            ->value('id');

        if ($existingId !== null) {
            $this->table()
                ->where('id', $existingId)
                ->update($payload + ['updated_at' => now()]);

            return;
        }

        $this->table()->insert($payload + [
            'owner_email' => $ownerEmail,
            'key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(?string $ownerEmail, array $data): void
    {
        $key = 'custom-'.Str::ulid()->toBase32();

        $this->save($ownerEmail, $key, $data);
    }

    public function delete(?string $ownerEmail, string $key): void
    {
        if (! str_starts_with($key, 'custom-') || ! $this->tableExists()) {
            return;
        }

        $this->table()
            ->where('owner_email', $ownerEmail)
            ->where('key', $key)
            ->delete();
    }

    /**
     * @return array<int, array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, columns: array<int, string>, isDefault: bool}>
     */
    private function defaultViews(): array
    {
        return [
            [
                'id' => 'all',
                'name' => 'All employees',
                'icon' => 'users',
                'filterQuery' => '',
                'sortColumn' => 'employee',
                'sortDirection' => 'asc',
                'columns' => ['employee', 'work_email', 'employment_status', 'access_status'],
                'isDefault' => true,
            ],
            [
                'id' => 'active',
                'name' => 'Active',
                'icon' => 'briefcase',
                'filterQuery' => 'employment:active',
                'sortColumn' => 'employee',
                'sortDirection' => 'asc',
                'columns' => ['employee', 'work_email', 'joined_on', 'access_status'],
                'isDefault' => true,
            ],
            [
                'id' => 'invited',
                'name' => 'Invited',
                'icon' => 'mail',
                'filterQuery' => 'access:invited',
                'sortColumn' => 'joined_on',
                'sortDirection' => 'desc',
                'columns' => ['employee', 'work_email', 'access_status', 'user_id'],
                'isDefault' => true,
            ],
            [
                'id' => 'access-pending',
                'name' => 'Access pending',
                'icon' => 'clock',
                'filterQuery' => 'access:not_invited',
                'sortColumn' => 'employee',
                'sortDirection' => 'asc',
                'columns' => ['employee', 'work_email', 'access_status'],
                'isDefault' => true,
            ],
        ];
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
     * @return array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, columns: array<int, string>, isDefault: bool}
     */
    private function rowToView(object $row): array
    {
        return [
            'id' => $this->stringValue($row->key ?? null, 'all'),
            'name' => $this->stringValue($row->name ?? null, 'Employees'),
            'icon' => $this->stringValue($row->icon ?? null, 'eye'),
            'filterQuery' => $this->stringValue($row->filter_query ?? null),
            'sortColumn' => $this->stringValue($row->sort_column ?? null, 'employee'),
            'sortDirection' => $this->stringValue($row->sort_direction ?? null, 'asc'),
            'columns' => $this->stringList($row->columns ?? null, ['employee']),
            'isDefault' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name' => $this->stringValue($data['name'] ?? null, 'Employees'),
            'icon' => $this->stringValue($data['icon'] ?? null, 'eye'),
            'filter_query' => $this->stringValue($data['filterQuery'] ?? null),
            'sort_column' => $this->stringValue($data['sortColumn'] ?? null, 'employee'),
            'sort_direction' => $this->stringValue($data['sortDirection'] ?? null, 'asc'),
            'columns' => json_encode($this->stringList($data['columns'] ?? null, ['employee'])) ?: '[]',
        ];
    }

    private function table(): Builder
    {
        return DB::connection($this->connectionName())->table($this->tableName());
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
        return $this->configString('employeon.employees.views_table', 'employee_views');
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
}
