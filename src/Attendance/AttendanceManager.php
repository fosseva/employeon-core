<?php

declare(strict_types=1);

namespace Employeon\Attendance;

use Employeon\Contracts\DatabaseConnectionResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class AttendanceManager
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    /**
     * @return array{
     *     employees: array<int, array{id: int, name: string, work_email: string}>,
     *     entries: array<int, array{id: int, employee_id: int, employee_name: string, attendance_date: string, status: string, check_in_at: string, check_out_at: string, notes: string}>,
     *     stats: array{present: int, absent: int, on_leave: int}
     * }
     */
    public function pageData(): array
    {
        return [
            'employees' => $this->employees(),
            'entries' => $this->entries(),
            'stats' => $this->stats(),
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function create(array $data): void
    {
        $this->table()->insert($this->payload($data) + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function update(int $entryId, array $data): void
    {
        $this->table()
            ->where('id', $entryId)
            ->update($this->payload($data) + [
                'updated_at' => now(),
            ]);
    }

    public function delete(int $entryId): void
    {
        $this->table()->where('id', $entryId)->delete();
    }

    /**
     * @return array<int, array{id: int, name: string, work_email: string}>
     */
    private function employees(): array
    {
        if (! $this->tableExists($this->employeesTable())) {
            return [];
        }

        $employees = [];
        $rows = DB::connection($this->connectionName())
            ->table($this->employeesTable())
            ->select(['id', 'first_name', 'last_name', 'display_name', 'work_email'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(100)
            ->get();

        foreach ($rows as $employee) {
            $firstName = $this->stringValue($employee->first_name ?? null);
            $lastName = $this->stringValue($employee->last_name ?? null);
            $displayName = $this->stringValue($employee->display_name ?? null);

            $employees[] = [
                'id' => $this->integerValue($employee->id ?? null),
                'name' => $displayName !== '' ? $displayName : trim($firstName.' '.$lastName),
                'work_email' => $this->stringValue($employee->work_email ?? null),
            ];
        }

        return $employees;
    }

    /**
     * @return array<int, array{id: int, employee_id: int, employee_name: string, attendance_date: string, status: string, check_in_at: string, check_out_at: string, notes: string}>
     */
    private function entries(): array
    {
        if (! $this->tableExists($this->tableName()) || ! $this->tableExists($this->employeesTable())) {
            return [];
        }

        $entries = [];
        $rows = $this->table()
            ->join($this->employeesTable(), $this->employeesTable().'.id', '=', $this->tableName().'.employee_id')
            ->select([
                $this->tableName().'.id',
                $this->tableName().'.employee_id',
                $this->tableName().'.attendance_date',
                $this->tableName().'.status',
                $this->tableName().'.check_in_at',
                $this->tableName().'.check_out_at',
                $this->tableName().'.notes',
                $this->employeesTable().'.first_name',
                $this->employeesTable().'.last_name',
                $this->employeesTable().'.display_name',
            ])
            ->orderByDesc($this->tableName().'.attendance_date')
            ->limit(100)
            ->get();

        foreach ($rows as $entry) {
            $firstName = $this->stringValue($entry->first_name ?? null);
            $lastName = $this->stringValue($entry->last_name ?? null);
            $displayName = $this->stringValue($entry->display_name ?? null);

            $entries[] = [
                'id' => $this->integerValue($entry->id ?? null),
                'employee_id' => $this->integerValue($entry->employee_id ?? null),
                'employee_name' => $displayName !== '' ? $displayName : trim($firstName.' '.$lastName),
                'attendance_date' => $this->stringValue($entry->attendance_date ?? null),
                'status' => $this->stringValue($entry->status ?? null),
                'check_in_at' => $this->stringValue($entry->check_in_at ?? null),
                'check_out_at' => $this->stringValue($entry->check_out_at ?? null),
                'notes' => $this->stringValue($entry->notes ?? null),
            ];
        }

        return $entries;
    }

    /**
     * @return array{present: int, absent: int, on_leave: int}
     */
    private function stats(): array
    {
        if (! $this->tableExists($this->tableName())) {
            return [
                'present' => 0,
                'absent' => 0,
                'on_leave' => 0,
            ];
        }

        return [
            'present' => $this->table()->where('status', 'present')->count(),
            'absent' => $this->table()->where('status', 'absent')->count(),
            'on_leave' => $this->table()->where('status', 'on_leave')->count(),
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<string, int|string|null>
     */
    private function payload(array $data): array
    {
        return [
            'employee_id' => is_numeric($data['employee_id'] ?? null) ? (int) $data['employee_id'] : 0,
            'attendance_date' => $data['attendance_date'] ?: now()->toDateString(),
            'status' => $data['status'] ?: 'present',
            'check_in_at' => $data['check_in_at'] ?: null,
            'check_out_at' => $data['check_out_at'] ?: null,
            'notes' => $data['notes'] ?: null,
        ];
    }

    private function table(): Builder
    {
        return DB::connection($this->connectionName())->table($this->tableName());
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
        return $this->configString('employeon.attendance.table', 'attendance_entries');
    }

    private function employeesTable(): string
    {
        return $this->configString('employeon.employees.table', 'employees');
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
