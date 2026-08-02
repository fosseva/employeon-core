<?php

declare(strict_types=1);

namespace Employeon\Support;

use Employeon\Contracts\DatabaseConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

final readonly class AccessManager
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    /**
     * @return array{
     *     roles: array<int, array{id: int, name: string, permission_ids: array<int, int>, user_count: int}>,
     *     permissions: array<int, array{id: int, name: string, role_ids: array<int, int>}>,
     *     users: array<int, array{id: int, name: string, email: string, role_ids: array<int, int>}>
     * }
     */
    public function pageData(): array
    {
        return [
            'roles' => $this->roles(),
            'permissions' => $this->permissions(),
            'users' => $this->users(),
        ];
    }

    public function createRole(string $name): void
    {
        $this->table($this->rolesTable())->insert([
            'name' => $name,
            'guard_name' => $this->guardName(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->forgetCachedPermissions();
    }

    public function updateRole(int $roleId, string $name): void
    {
        $this->table($this->rolesTable())
            ->where('id', $roleId)
            ->update([
                'name' => $name,
                'updated_at' => now(),
            ]);

        $this->forgetCachedPermissions();
    }

    public function deleteRole(int $roleId): void
    {
        $this->table($this->roleHasPermissionsTable())->where($this->rolePivotKey(), $roleId)->delete();
        $this->table($this->modelHasRolesTable())->where($this->rolePivotKey(), $roleId)->delete();
        $this->table($this->rolesTable())->where('id', $roleId)->delete();

        $this->forgetCachedPermissions();
    }

    public function createPermission(string $name): void
    {
        $this->table($this->permissionsTable())->insert([
            'name' => $name,
            'guard_name' => $this->guardName(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->forgetCachedPermissions();
    }

    public function updatePermission(int $permissionId, string $name): void
    {
        $this->table($this->permissionsTable())
            ->where('id', $permissionId)
            ->update([
                'name' => $name,
                'updated_at' => now(),
            ]);

        $this->forgetCachedPermissions();
    }

    public function deletePermission(int $permissionId): void
    {
        $this->table($this->roleHasPermissionsTable())->where($this->permissionPivotKey(), $permissionId)->delete();
        $this->table($this->modelHasPermissionsTable())->where($this->permissionPivotKey(), $permissionId)->delete();
        $this->table($this->permissionsTable())->where('id', $permissionId)->delete();

        $this->forgetCachedPermissions();
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->table($this->roleHasPermissionsTable())->where($this->rolePivotKey(), $roleId)->delete();

        foreach (array_values(array_unique($permissionIds)) as $permissionId) {
            $this->table($this->roleHasPermissionsTable())->insert([
                $this->rolePivotKey() => $roleId,
                $this->permissionPivotKey() => $permissionId,
            ]);
        }

        $this->forgetCachedPermissions();
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncUserRoles(int $userId, array $roleIds): void
    {
        $userModel = $this->userModelClass();

        if ($userModel === null) {
            return;
        }

        $this->table($this->modelHasRolesTable())
            ->where($this->modelMorphKey(), $userId)
            ->where('model_type', $userModel)
            ->delete();

        foreach (array_values(array_unique($roleIds)) as $roleId) {
            $this->table($this->modelHasRolesTable())->insert([
                $this->rolePivotKey() => $roleId,
                $this->modelMorphKey() => $userId,
                'model_type' => $userModel,
            ]);
        }

        $this->forgetCachedPermissions();
    }

    /**
     * @return array<int, array{id: int, name: string, permission_ids: array<int, int>, user_count: int}>
     */
    private function roles(): array
    {
        if (! $this->tableExists($this->rolesTable())) {
            return [];
        }

        $roles = [];
        $rows = $this->table($this->rolesTable())
            ->where('guard_name', $this->guardName())
            ->orderBy('name')
            ->get();

        foreach ($rows as $role) {
            $roleId = $this->integerValue($role->id ?? null);

            $roles[] = [
                'id' => $roleId,
                'name' => $this->stringValue($role->name ?? null),
                'permission_ids' => $this->rolePermissionIds($roleId),
                'user_count' => $this->roleUserCount($roleId),
            ];
        }

        return $roles;
    }

    /**
     * @return array<int, array{id: int, name: string, role_ids: array<int, int>}>
     */
    private function permissions(): array
    {
        if (! $this->tableExists($this->permissionsTable())) {
            return [];
        }

        $permissions = [];
        $rows = $this->table($this->permissionsTable())
            ->where('guard_name', $this->guardName())
            ->orderBy('name')
            ->get();

        foreach ($rows as $permission) {
            $permissionId = $this->integerValue($permission->id ?? null);

            $permissions[] = [
                'id' => $permissionId,
                'name' => $this->stringValue($permission->name ?? null),
                'role_ids' => $this->permissionRoleIds($permissionId),
            ];
        }

        return $permissions;
    }

    /**
     * @return array<int, array{id: int, name: string, email: string, role_ids: array<int, int>}>
     */
    private function users(): array
    {
        $userModel = $this->userModelClass();

        if ($userModel === null) {
            return [];
        }

        /** @var Model $model */
        $model = new $userModel;
        $connection = $this->connectionName();

        if ($connection !== null) {
            $model->setConnection($connection);
        }

        if (! $this->tableExists($model->getTable())) {
            return [];
        }

        $nameColumn = $this->userNameColumn();
        $emailColumn = $this->userEmailColumn();

        $users = [];
        $rows = $this->table($model->getTable())
            ->select([$model->getKeyName(), $nameColumn, $emailColumn])
            ->orderBy($nameColumn)
            ->limit(50)
            ->get();

        foreach ($rows as $user) {
            $keyName = $model->getKeyName();
            $userId = $this->integerValue($user->{$keyName} ?? null);

            $users[] = [
                'id' => $userId,
                'name' => $this->stringValue($user->{$nameColumn} ?? null),
                'email' => $this->stringValue($user->{$emailColumn} ?? null),
                'role_ids' => $this->userRoleIds($userId, $userModel),
            ];
        }

        return $users;
    }

    /**
     * @return array<int, int>
     */
    private function rolePermissionIds(int $roleId): array
    {
        if (! $this->tableExists($this->roleHasPermissionsTable())) {
            return [];
        }

        return $this->table($this->roleHasPermissionsTable())
            ->where($this->rolePivotKey(), $roleId)
            ->pluck($this->permissionPivotKey())
            ->map(fn (mixed $id): int => $this->integerValue($id))
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function permissionRoleIds(int $permissionId): array
    {
        if (! $this->tableExists($this->roleHasPermissionsTable())) {
            return [];
        }

        return $this->table($this->roleHasPermissionsTable())
            ->where($this->permissionPivotKey(), $permissionId)
            ->pluck($this->rolePivotKey())
            ->map(fn (mixed $id): int => $this->integerValue($id))
            ->values()
            ->all();
    }

    private function roleUserCount(int $roleId): int
    {
        if (! $this->tableExists($this->modelHasRolesTable())) {
            return 0;
        }

        return $this->table($this->modelHasRolesTable())
            ->where($this->rolePivotKey(), $roleId)
            ->count();
    }

    /**
     * @return array<int, int>
     */
    private function userRoleIds(int $userId, string $userModel): array
    {
        if (! $this->tableExists($this->modelHasRolesTable())) {
            return [];
        }

        return $this->table($this->modelHasRolesTable())
            ->where($this->modelMorphKey(), $userId)
            ->where('model_type', $userModel)
            ->pluck($this->rolePivotKey())
            ->map(fn (mixed $id): int => $this->integerValue($id))
            ->values()
            ->all();
    }

    private function table(string $table): Builder
    {
        return DB::connection($this->connectionName())->table($table);
    }

    private function tableExists(string $table): bool
    {
        if (! $this->connectionIsConfigured()) {
            return false;
        }

        return Schema::connection($this->connectionName())->hasTable($table);
    }

    private function connectionIsConfigured(): bool
    {
        $connection = $this->connectionName();

        if ($connection === null) {
            return true;
        }

        return is_array(config('database.connections.'.$connection));
    }

    private function connectionName(): ?string
    {
        return $this->databaseConnectionResolver->resolve();
    }

    private function guardName(): string
    {
        return $this->configString('employeon.access.guard', 'web');
    }

    private function rolesTable(): string
    {
        return $this->configString('permission.table_names.roles', 'roles');
    }

    private function permissionsTable(): string
    {
        return $this->configString('permission.table_names.permissions', 'permissions');
    }

    private function roleHasPermissionsTable(): string
    {
        return $this->configString('permission.table_names.role_has_permissions', 'role_has_permissions');
    }

    private function modelHasRolesTable(): string
    {
        return $this->configString('permission.table_names.model_has_roles', 'model_has_roles');
    }

    private function modelHasPermissionsTable(): string
    {
        return $this->configString('permission.table_names.model_has_permissions', 'model_has_permissions');
    }

    private function rolePivotKey(): string
    {
        return $this->configString('permission.column_names.role_pivot_key', 'role_id');
    }

    private function permissionPivotKey(): string
    {
        return $this->configString('permission.column_names.permission_pivot_key', 'permission_id');
    }

    private function modelMorphKey(): string
    {
        return $this->configString('permission.column_names.model_morph_key', 'model_id');
    }

    private function userNameColumn(): string
    {
        return $this->configString('employeon.access.user_name_column', 'name');
    }

    private function userEmailColumn(): string
    {
        return $this->configString('employeon.access.user_email_column', 'email');
    }

    private function userModelClass(): ?string
    {
        $configuredModel = config('employeon.access.user_model');

        if (is_string($configuredModel) && class_exists($configuredModel)) {
            return $configuredModel;
        }

        $provider = config('auth.guards.'.$this->guardName().'.provider');
        $guardModel = is_string($provider) ? config('auth.providers.'.$provider.'.model') : null;

        if (is_string($guardModel) && class_exists($guardModel)) {
            return $guardModel;
        }

        return null;
    }

    private function configString(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return '';
    }

    private function forgetCachedPermissions(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
