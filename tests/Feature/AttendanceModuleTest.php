<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withSession([
        'employeon.user' => [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ],
    ]);

    Schema::dropIfExists('attendance_entries');
    Schema::dropIfExists('employees');

    Schema::create('employees', function (Blueprint $table): void {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('display_name')->nullable();
        $table->string('work_email')->nullable();
        $table->timestamps();
    });

    Schema::create('attendance_entries', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('employee_id');
        $table->date('attendance_date');
        $table->string('status')->default('present');
        $table->dateTime('check_in_at')->nullable();
        $table->dateTime('check_out_at')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->unique(['employee_id', 'attendance_date']);
    });
});

it('creates and renders attendance entries', function (): void {
    $this->withoutVite();

    $employeeId = DB::table('employees')->insertGetId([
        'first_name' => 'Asha',
        'last_name' => 'Rao',
        'display_name' => 'Asha Rao',
        'work_email' => 'asha@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post('/attendance', [
        'employee_id' => $employeeId,
        'attendance_date' => '2026-01-12',
        'status' => 'present',
        'check_in_at' => '2026-01-12 09:00:00',
        'check_out_at' => '2026-01-12 18:00:00',
        'notes' => 'On time',
    ])->assertRedirect();

    $this->get('/attendance')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Attendance/Index')
            ->where('employees.0.name', 'Asha Rao')
            ->where('entries.0.employee_name', 'Asha Rao')
            ->where('entries.0.status', 'present')
            ->where('stats.present', 1)
        );
});

it('updates and deletes attendance entries', function (): void {
    $employeeId = DB::table('employees')->insertGetId([
        'first_name' => 'Asha',
        'last_name' => 'Rao',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $entryId = DB::table('attendance_entries')->insertGetId([
        'employee_id' => $employeeId,
        'attendance_date' => '2026-01-12',
        'status' => 'present',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->put("/attendance/{$entryId}", [
        'employee_id' => $employeeId,
        'attendance_date' => '2026-01-12',
        'status' => 'absent',
        'check_in_at' => '',
        'check_out_at' => '',
        'notes' => 'Sick leave pending',
    ])->assertRedirect();

    expect(DB::table('attendance_entries')->where('id', $entryId)->value('status'))->toBe('absent');

    $this->delete("/attendance/{$entryId}")->assertRedirect();

    expect(DB::table('attendance_entries')->where('id', $entryId)->exists())->toBeFalse();
});
