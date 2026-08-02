<?php

declare(strict_types=1);

namespace Employeon\Employees;

use Employeon\Contracts\DatabaseConnectionResolver;
use Employeon\Employees\Events\EmployeeInvited;
use Employeon\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final readonly class EmployeeManager
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    /**
     * @return array{
     *     employees: array<int, array{id: int, user_id: int|null, employee_number: string, first_name: string, middle_name: string, last_name: string, display_name: string, work_email: string, personal_email: string, employment_status: string, joined_on: string, access_status: string, invited_at: string, invite_accepted_at: string, access_disabled_at: string}>,
     *     stats: array{total: int, active: int, inactive: int, invited: int, linked: int},
     *     access: array{can_manage_users: bool}
     * }
     */
    public function pageData(): array
    {
        return [
            'employees' => $this->employees(),
            'stats' => $this->stats(),
            'access' => [
                'can_manage_users' => $this->canManageUsers(),
            ],
        ];
    }

    /**
     * @return array{id: int, user_id: int|null, employee_number: string, first_name: string, middle_name: string, last_name: string, display_name: string, work_email: string, personal_email: string, employment_status: string, joined_on: string, access_status: string, invited_at: string, invite_accepted_at: string, access_disabled_at: string}|null
     */
    public function find(int $employeeId): ?array
    {
        $employee = $this->employee($employeeId);

        if ($employee === null) {
            return null;
        }

        return $this->employeeRow($employee);
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function create(array $data): void
    {
        $this->employeeModel()->newQuery()->create($this->payload($data));
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function update(int $employeeId, array $data): void
    {
        $this->employeeQuery()
            ->whereKey($employeeId)
            ->update($this->payload($data) + [
                'updated_at' => now(),
            ]);
    }

    public function delete(int $employeeId): void
    {
        DB::connection($this->connectionName())
            ->table($this->attendanceTable())
            ->where('employee_id', $employeeId)
            ->delete();

        $this->employeeQuery()->whereKey($employeeId)->delete();
    }

    /**
     * @param  array<int, int>  $employeeIds
     */
    public function deleteMany(array $employeeIds): void
    {
        $employeeIds = array_values(array_unique(array_filter($employeeIds, fn (int $employeeId): bool => $employeeId > 0)));

        if ($employeeIds === []) {
            return;
        }

        DB::connection($this->connectionName())
            ->table($this->attendanceTable())
            ->whereIn('employee_id', $employeeIds)
            ->delete();

        $this->employeeQuery()->whereKey($employeeIds)->delete();
    }

    /**
     * @return array{user_id: int|null, invite_token: string}
     */
    public function invite(int $employeeId): array
    {
        $employee = $this->employee($employeeId);

        if ($employee === null) {
            return [
                'user_id' => null,
                'invite_token' => '',
            ];
        }

        $userId = $this->findOrCreateUser($employee);
        $inviteToken = Str::random(48);

        $this->employeeQuery()
            ->whereKey($employeeId)
            ->update([
                'user_id' => $userId,
                'access_status' => 'invited',
                'invite_token' => $inviteToken,
                'invited_at' => now(),
                'access_disabled_at' => null,
                'updated_at' => now(),
            ]);

        $email = $this->stringValue($employee->work_email ?? null);
        $inviteUrl = $this->inviteUrl($inviteToken, $email);

        event(new EmployeeInvited(
            employeeId: $employeeId,
            userId: $userId,
            email: $email,
            token: $inviteToken,
            inviteUrl: $inviteUrl,
        ));

        $this->sendInvitationNotification($userId, $employee, $inviteUrl);

        return [
            'user_id' => $userId,
            'invite_token' => $inviteToken,
        ];
    }

    public function linkExistingUser(int $employeeId, string $email): ?int
    {
        $userId = $this->findUserIdByEmail($email);

        if ($userId === null) {
            return null;
        }

        $this->employeeQuery()
            ->whereKey($employeeId)
            ->update([
                'user_id' => $userId,
                'access_status' => 'active',
                'invite_token' => null,
                'invite_accepted_at' => now(),
                'access_disabled_at' => null,
                'updated_at' => now(),
            ]);

        return $userId;
    }

    public function disableAccess(int $employeeId): void
    {
        $this->employeeQuery()
            ->whereKey($employeeId)
            ->update([
                'access_status' => 'disabled',
                'access_disabled_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function unlinkAccess(int $employeeId): void
    {
        $this->employeeQuery()
            ->whereKey($employeeId)
            ->update([
                'user_id' => null,
                'access_status' => 'not_invited',
                'invite_token' => null,
                'invited_at' => null,
                'invite_accepted_at' => null,
                'access_disabled_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array{user_id: int|null, invite_token: string}
     */
    public function resetInvite(int $employeeId): array
    {
        return $this->invite($employeeId);
    }

    /**
     * @return array{user_id: int, name: string, email: string, role: string}|null
     */
    public function acceptInvitation(string $token): ?array
    {
        if ($token === '' || ! $this->tableExists($this->tableName())) {
            return null;
        }

        $employee = $this->employeeQuery()
            ->where('invite_token', $token)
            ->whereNotNull('user_id')
            ->first();

        if ($employee === null) {
            return null;
        }

        $userId = $this->nullableIntegerValue($employee->user_id ?? null);

        if ($userId === null) {
            return null;
        }

        $this->employeeQuery()
            ->whereKey($this->integerValue($employee->id ?? null))
            ->update([
                'access_status' => 'active',
                'invite_token' => null,
                'invite_accepted_at' => now(),
                'access_disabled_at' => null,
                'updated_at' => now(),
            ]);

        return [
            'user_id' => $userId,
            'name' => $this->employeeDisplayName($employee),
            'email' => $this->stringValue($employee->work_email ?? null),
            'role' => 'Employee',
        ];
    }

    /**
     * @return array<int, array{id: int, user_id: int|null, employee_number: string, first_name: string, middle_name: string, last_name: string, display_name: string, work_email: string, personal_email: string, employment_status: string, joined_on: string, access_status: string, invited_at: string, invite_accepted_at: string, access_disabled_at: string}>
     */
    private function employees(): array
    {
        if (! $this->tableExists($this->tableName())) {
            return [];
        }

        $employees = [];
        $rows = $this->employeeQuery()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        foreach ($rows as $employee) {
            $employees[] = $this->employeeRow($employee);
        }

        return $employees;
    }

    /**
     * @return array{id: int, user_id: int|null, employee_number: string, first_name: string, middle_name: string, last_name: string, display_name: string, work_email: string, personal_email: string, employment_status: string, joined_on: string, access_status: string, invited_at: string, invite_accepted_at: string, access_disabled_at: string}
     */
    private function employeeRow(object $employee): array
    {
        return [
            'id' => $this->integerValue($employee->id ?? null),
            'user_id' => $this->nullableIntegerValue($employee->user_id ?? null),
            'employee_number' => $this->stringValue($employee->employee_number ?? null),
            'first_name' => $this->stringValue($employee->first_name ?? null),
            'middle_name' => $this->stringValue($employee->middle_name ?? null),
            'last_name' => $this->stringValue($employee->last_name ?? null),
            'display_name' => $this->stringValue($employee->display_name ?? null),
            'work_email' => $this->stringValue($employee->work_email ?? null),
            'personal_email' => $this->stringValue($employee->personal_email ?? null),
            'employment_status' => $this->stringValue($employee->employment_status ?? null),
            'joined_on' => $this->stringValue($employee->joined_on ?? null),
            'access_status' => $this->stringValue($employee->access_status ?? null),
            'invited_at' => $this->stringValue($employee->invited_at ?? null),
            'invite_accepted_at' => $this->stringValue($employee->invite_accepted_at ?? null),
            'access_disabled_at' => $this->stringValue($employee->access_disabled_at ?? null),
        ];
    }

    /**
     * @return array{total: int, active: int, inactive: int, invited: int, linked: int}
     */
    private function stats(): array
    {
        if (! $this->tableExists($this->tableName())) {
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'invited' => 0,
                'linked' => 0,
            ];
        }

        return [
            'total' => $this->employeeQuery()->count(),
            'active' => $this->employeeQuery()->where('employment_status', 'active')->count(),
            'inactive' => $this->employeeQuery()->where('employment_status', 'inactive')->count(),
            'invited' => $this->employeeQuery()->where('access_status', 'invited')->count(),
            'linked' => $this->employeeQuery()->whereNotNull('user_id')->count(),
        ];
    }

    private function employee(int $employeeId): ?Employee
    {
        if (! $this->tableExists($this->tableName())) {
            return null;
        }

        $employee = $this->employeeQuery()->whereKey($employeeId)->first();

        return $employee instanceof Employee ? $employee : null;
    }

    private function findOrCreateUser(object $employee): ?int
    {
        $email = $this->stringValue($employee->work_email ?? null);

        if ($email === '' || ! $this->canManageUsers()) {
            return null;
        }

        $existingUserId = $this->findUserIdByEmail($email);

        if ($existingUserId !== null) {
            return $existingUserId;
        }

        $userModel = $this->userModelClass();

        if ($userModel === null) {
            return null;
        }

        /** @var Model $model */
        $model = new $userModel;
        $table = $model->getTable();
        $name = $this->employeeDisplayName($employee);

        $payload = [
            $this->userEmailColumn() => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::connection($this->connectionName())->hasColumn($table, $this->userNameColumn())) {
            $payload[$this->userNameColumn()] = $name;
        }

        if (Schema::connection($this->connectionName())->hasColumn($table, 'password')) {
            $payload['password'] = Hash::make(Str::random(32));
        }

        return (int) DB::connection($this->connectionName())
            ->table($table)
            ->insertGetId($payload);
    }

    private function findUserIdByEmail(string $email): ?int
    {
        $userModel = $this->userModelClass();

        if ($email === '' || $userModel === null || ! $this->canManageUsers()) {
            return null;
        }

        /** @var Model $model */
        $model = new $userModel;
        $id = DB::connection($this->connectionName())
            ->table($model->getTable())
            ->where($this->userEmailColumn(), $email)
            ->value($model->getKeyName());

        return $this->nullableIntegerValue($id);
    }

    private function sendInvitationNotification(?int $userId, object $employee, string $inviteUrl): void
    {
        if ($userId === null || ! $this->shouldSendInvitationNotification()) {
            return;
        }

        $user = $this->user($userId);

        if ($user === null || ! method_exists($user, 'notify')) {
            return;
        }

        $notification = $this->invitationNotification($employee, $inviteUrl);

        if ($notification === null) {
            return;
        }

        $user->notify($notification);
    }

    private function user(int $userId): ?Model
    {
        $userModel = $this->userModelClass();

        if ($userModel === null || ! $this->canManageUsers()) {
            return null;
        }

        /** @var Model $model */
        $model = new $userModel;
        $connection = $this->connectionName();

        if ($connection !== null) {
            $model->setConnection($connection);
        }

        $user = $model->newQuery()->whereKey($userId)->first();

        return $user instanceof Model ? $user : null;
    }

    private function invitationNotification(object $employee, string $inviteUrl): ?Notification
    {
        $notificationClass = config('employeon.employees.invitations.notification');

        if (! is_string($notificationClass) || ! is_subclass_of($notificationClass, Notification::class)) {
            return null;
        }

        return app($notificationClass, [
            'employeeName' => $this->employeeDisplayName($employee),
            'inviteUrl' => $inviteUrl,
        ]);
    }

    private function shouldSendInvitationNotification(): bool
    {
        return config('employeon.employees.invitations.send_notification', true) === true;
    }

    private function inviteUrl(string $token, string $email): string
    {
        $configuredUrl = config('employeon.employees.invitations.url');

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            return str_replace(
                ['{token}', '{email}'],
                [$token, rawurlencode($email)],
                $configuredUrl,
            );
        }

        $appUrl = $this->configString('app.url', '');
        $path = '/employee-invitations/'.$token;

        return $appUrl !== '' ? rtrim($appUrl, '/').$path : $path;
    }

    public function canManageUsers(): bool
    {
        $userModel = $this->userModelClass();

        if ($userModel === null) {
            return false;
        }

        /** @var Model $model */
        $model = new $userModel;
        $connection = $this->connectionName();

        if ($connection !== null && ! is_array(config('database.connections.'.$connection))) {
            return false;
        }

        return Schema::connection($connection)->hasTable($model->getTable())
            && Schema::connection($connection)->hasColumn($model->getTable(), $this->userEmailColumn());
    }

    private function userModelClass(): ?string
    {
        $configuredModel = config('employeon.access.user_model');

        if (is_string($configuredModel) && class_exists($configuredModel)) {
            return $configuredModel;
        }

        $provider = config('auth.guards.'.$this->configString('employeon.access.guard', 'web').'.provider');
        $guardModel = is_string($provider) ? config('auth.providers.'.$provider.'.model') : null;

        if (is_string($guardModel) && class_exists($guardModel)) {
            return $guardModel;
        }

        return null;
    }

    private function userNameColumn(): string
    {
        return $this->configString('employeon.access.user_name_column', 'name');
    }

    private function userEmailColumn(): string
    {
        return $this->configString('employeon.access.user_email_column', 'email');
    }

    private function employeeDisplayName(object $employee): string
    {
        $displayName = $this->stringValue($employee->display_name ?? null);

        if ($displayName !== '') {
            return $displayName;
        }

        return trim($this->stringValue($employee->first_name ?? null).' '.$this->stringValue($employee->last_name ?? null));
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<string, string|null>
     */
    private function payload(array $data): array
    {
        $displayName = $data['display_name'] ?: trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        return [
            'employee_number' => $data['employee_number'] ?: null,
            'first_name' => $data['first_name'] ?? '',
            'middle_name' => $data['middle_name'] ?: null,
            'last_name' => $data['last_name'] ?? '',
            'display_name' => $displayName !== '' ? $displayName : null,
            'work_email' => $data['work_email'] ?: null,
            'personal_email' => $data['personal_email'] ?: null,
            'employment_status' => $data['employment_status'] ?: 'active',
            'joined_on' => $data['joined_on'] ?: null,
        ];
    }

    /**
     * @return Builder<Employee>
     */
    private function employeeQuery(): Builder
    {
        return $this->employeeModel()->newQuery();
    }

    private function employeeModel(): Employee
    {
        $model = new Employee;
        $model->setTable($this->tableName());

        $connection = $this->connectionName();

        if ($connection !== null) {
            $model->setConnection($connection);
        }

        return $model;
    }

    private function tableExists(string $table): bool
    {
        $connection = $this->connectionName();

        if ($connection !== null && ! is_array(config('database.connections.'.$connection))) {
            return false;
        }

        return Schema::connection($connection)->hasTable($table);
    }

    private function tableName(): string
    {
        return $this->configString('employeon.employees.table', 'employees');
    }

    private function attendanceTable(): string
    {
        return $this->configString('employeon.attendance.table', 'attendance_entries');
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

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function nullableIntegerValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
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
}
