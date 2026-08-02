<?php

declare(strict_types=1);

namespace Employeon\Employees\Database\Seeders;

use Employeon\Employees\Models\Employee;
use Illuminate\Database\Seeder;

final class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::factory()->count(350)->active()->accessActive()->create();
        Employee::factory()->count(200)->active()->notInvited()->create();
        Employee::factory()->count(150)->active()->invited()->create();
        Employee::factory()->count(100)->onLeave()->accessActive()->create();
        Employee::factory()->count(100)->inactive()->accessDisabled()->create();
        Employee::factory()->count(100)->inactive()->notInvited()->create();
    }
}
