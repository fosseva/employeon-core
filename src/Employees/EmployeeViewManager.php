<?php

declare(strict_types=1);

namespace Employeon\Employees;

use Employeon\Support\SavedViewManager;
use Employeon\Support\SavedViewOwner;

final readonly class EmployeeViewManager
{
    public function __construct(
        private SavedViewManager $savedViewManager,
    ) {}

    /**
     * @return array<int, array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, sorts: array<int, array{column: string, direction: string}>, columns: array<int, string>, isDefault: bool}>
     */
    public function views(?SavedViewOwner $owner): array
    {
        return $this->savedViewManager->views($owner, $this->viewableType(), $this->defaultViews());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(?SavedViewOwner $owner, string $key, array $data): void
    {
        $this->savedViewManager->save($owner, $this->viewableType(), $key, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(?SavedViewOwner $owner, array $data): void
    {
        $this->savedViewManager->create($owner, $this->viewableType(), $data);
    }

    public function delete(?SavedViewOwner $owner, string $key): void
    {
        $this->savedViewManager->delete($owner, $this->viewableType(), $key);
    }

    /**
     * @return array<int, array{id: string, name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, sorts: array<int, array{column: string, direction: string}>, columns: array<int, string>, isDefault: bool}>
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
                'sorts' => [['column' => 'employee', 'direction' => 'asc']],
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
                'sorts' => [['column' => 'employee', 'direction' => 'asc']],
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
                'sorts' => [['column' => 'joined_on', 'direction' => 'desc']],
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
                'sorts' => [['column' => 'employee', 'direction' => 'asc']],
                'columns' => ['employee', 'work_email', 'access_status'],
                'isDefault' => true,
            ],
        ];
    }

    private function viewableType(): string
    {
        return 'employees';
    }
}
