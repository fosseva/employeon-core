<?php

declare(strict_types=1);

namespace Employeon\Employees\Models;

use Employeon\Employees\Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function getTable(): string
    {
        $table = config('employeon.employees.table', parent::getTable());

        return is_string($table) && $table !== '' ? $table : parent::getTable();
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
