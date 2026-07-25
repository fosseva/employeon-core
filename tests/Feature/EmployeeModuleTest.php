<?php

declare(strict_types=1);

use Employeon\Employees\Events\EmployeeInvited;
use Employeon\Employees\Notifications\EmployeeInvitationNotification;
use Employeon\Tests\Fixtures\AccessUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
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
    Schema::dropIfExists('employee_views');
    Schema::dropIfExists('access_users');
    Schema::dropIfExists('employees');

    config()->set('employeon.access.user_model', AccessUser::class);

    Schema::create('employees', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('access_status')->default('not_invited');
        $table->string('invite_token')->nullable();
        $table->timestamp('invited_at')->nullable();
        $table->timestamp('invite_accepted_at')->nullable();
        $table->timestamp('access_disabled_at')->nullable();
        $table->string('employee_number')->nullable()->unique();
        $table->string('first_name');
        $table->string('middle_name')->nullable();
        $table->string('last_name');
        $table->string('display_name')->nullable();
        $table->string('work_email')->nullable()->unique();
        $table->string('personal_email')->nullable();
        $table->string('employment_status')->default('active');
        $table->date('joined_on')->nullable();
        $table->timestamps();
    });

    Schema::create('access_users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password')->nullable();
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
    });

    Schema::create('employee_views', function (Blueprint $table): void {
        $table->id();
        $table->string('owner_email')->nullable()->index();
        $table->string('key');
        $table->string('name');
        $table->string('icon')->default('eye');
        $table->string('search')->default('');
        $table->string('employment_status')->default('all');
        $table->string('access_status')->default('all');
        $table->string('sort_column')->default('employee');
        $table->string('sort_direction')->default('asc');
        $table->json('columns');
        $table->timestamps();
        $table->unique(['owner_email', 'key']);
    });
});

it('creates and renders employees', function (): void {
    $this->withoutVite();

    $this->post('/employees', [
        'employee_number' => 'EMP-001',
        'first_name' => 'Asha',
        'middle_name' => '',
        'last_name' => 'Rao',
        'display_name' => '',
        'work_email' => 'asha@example.com',
        'personal_email' => 'asha.personal@example.com',
        'employment_status' => 'active',
        'joined_on' => '2026-01-10',
    ])->assertRedirect();

    $this->get('/employees')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Employees/Index')
            ->where('employees.0.employee_number', 'EMP-001')
            ->where('employees.0.display_name', 'Asha Rao')
            ->where('employees.0.work_email', 'asha@example.com')
            ->where('stats.total', 1)
            ->where('stats.active', 1)
        );
});

it('renders dedicated create and edit employee pages', function (): void {
    $this->withoutVite();

    $employeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-002',
        'first_name' => 'Rohan',
        'last_name' => 'Magadum',
        'display_name' => 'Rohan Magadum',
        'work_email' => 'rohan@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/employees/create')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Employees/Form')
            ->where('employee', null)
            ->where('access.can_manage_users', true)
        );

    $this->get("/employees/{$employeeId}/edit")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Employees/Form')
            ->where('employee.id', $employeeId)
            ->where('employee.work_email', 'rohan@example.com')
            ->where('access.can_manage_users', true)
        );
});

it('persists employee directory views in the database', function (): void {
    $this->withoutVite();

    $this->post('/employees/views', [
        'name' => 'Work email audit',
        'icon' => 'mail',
        'search' => 'audit',
        'employmentStatus' => 'active',
        'accessStatus' => 'all',
        'sortColumn' => 'work_email',
        'sortDirection' => 'desc',
        'columns' => ['employee', 'work_email', 'joined_on'],
    ])->assertRedirect();

    $view = DB::table('employee_views')->where('name', 'Work email audit')->first();

    expect($view)->not->toBeNull()
        ->and($view?->owner_email)->toBe('admin@example.com')
        ->and($view?->icon)->toBe('mail')
        ->and($view?->search)->toBe('audit')
        ->and($view?->sort_column)->toBe('work_email');

    $this->get('/employees')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Employees/Index')
            ->where('views.4.name', 'Work email audit')
            ->where('views.4.icon', 'mail')
            ->where('views.4.search', 'audit')
            ->where('views.4.sortColumn', 'work_email')
            ->where('views.4.columns.1', 'work_email')
        );

    $this->put('/employees/views/'.$view?->key, [
        'name' => 'Updated audit',
        'icon' => 'shield',
        'search' => '',
        'employmentStatus' => 'all',
        'accessStatus' => 'invited',
        'sortColumn' => 'employee',
        'sortDirection' => 'asc',
        'columns' => ['employee', 'access_status'],
    ])->assertRedirect();

    expect(DB::table('employee_views')->where('id', $view?->id)->value('name'))->toBe('Updated audit');

    $this->delete('/employees/views/'.$view?->key)->assertRedirect();

    expect(DB::table('employee_views')->where('id', $view?->id)->exists())->toBeFalse();
});

it('invites employees by creating and linking a user account', function (): void {
    Event::fake([EmployeeInvited::class]);
    Notification::fake();

    $employeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-003',
        'first_name' => 'Asha',
        'last_name' => 'Rao',
        'display_name' => 'Asha Rao',
        'work_email' => 'asha@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/employees/{$employeeId}/invite")->assertRedirect();

    $employee = DB::table('employees')->where('id', $employeeId)->first();

    expect(DB::table('access_users')->where('email', 'asha@example.com')->exists())->toBeTrue()
        ->and($employee?->user_id)->not->toBeNull()
        ->and($employee?->access_status)->toBe('invited')
        ->and($employee?->invite_token)->not->toBeNull();

    Event::assertDispatched(EmployeeInvited::class, fn (EmployeeInvited $event): bool => $event->employeeId === $employeeId
        && $event->email === 'asha@example.com'
        && $event->token === $employee?->invite_token
        && str_contains($event->inviteUrl, (string) $employee?->invite_token));

    Notification::assertSentTo(
        AccessUser::query()->where('email', 'asha@example.com')->firstOrFail(),
        EmployeeInvitationNotification::class,
    );
});

it('supports custom invitation urls and disabling the default notification', function (): void {
    Event::fake([EmployeeInvited::class]);
    Notification::fake();

    config()->set('employeon.employees.invitations.url', 'https://app.example.test/accept/{token}?email={email}');
    config()->set('employeon.employees.invitations.send_notification', false);

    $employeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-006',
        'first_name' => 'Mira',
        'last_name' => 'Iyer',
        'display_name' => 'Mira Iyer',
        'work_email' => 'mira+hr@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/employees/{$employeeId}/invite")->assertRedirect();

    $employee = DB::table('employees')->where('id', $employeeId)->first();

    Event::assertDispatched(EmployeeInvited::class, fn (EmployeeInvited $event): bool => $event->inviteUrl === 'https://app.example.test/accept/'.$employee?->invite_token.'?email=mira%2Bhr%40example.com');

    Notification::assertNothingSent();
});

it('accepts invitation links and signs the employee into the starter shell', function (): void {
    $this->withoutVite();

    Notification::fake();

    $employeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-007',
        'first_name' => 'Leela',
        'last_name' => 'Patel',
        'display_name' => 'Leela Patel',
        'work_email' => 'leela@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/employees/{$employeeId}/invite")->assertRedirect();

    $token = DB::table('employees')->where('id', $employeeId)->value('invite_token');

    $this->get('/employee-invitations/'.$token)->assertRedirect('/profile');

    expect(DB::table('employees')->where('id', $employeeId)->value('access_status'))->toBe('active')
        ->and(DB::table('employees')->where('id', $employeeId)->value('invite_token'))->toBeNull();

    $this->get('/profile')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('user.email', 'leela@example.com')
            ->where('user.role', 'Employee')
        );
});

it('links an employee to an existing user account', function (): void {
    $employeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-004',
        'first_name' => 'Nina',
        'last_name' => 'Shah',
        'work_email' => 'nina.work@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = DB::table('access_users')->insertGetId([
        'name' => 'Nina Shah',
        'email' => 'nina.login@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/employees/{$employeeId}/link-user", [
        'email' => 'nina.login@example.com',
    ])->assertRedirect();

    expect(DB::table('employees')->where('id', $employeeId)->value('user_id'))->toBe($userId)
        ->and(DB::table('employees')->where('id', $employeeId)->value('access_status'))->toBe('active');
});

it('disables unlinks and resets employee access', function (): void {
    $userId = DB::table('access_users')->insertGetId([
        'name' => 'Ravi Mehta',
        'email' => 'ravi@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $employeeId = DB::table('employees')->insertGetId([
        'user_id' => $userId,
        'employee_number' => 'EMP-005',
        'first_name' => 'Ravi',
        'last_name' => 'Mehta',
        'work_email' => 'ravi@example.com',
        'employment_status' => 'active',
        'access_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post("/employees/{$employeeId}/disable-access")->assertRedirect();

    expect(DB::table('employees')->where('id', $employeeId)->value('access_status'))->toBe('disabled');

    $this->post("/employees/{$employeeId}/reset-invite")->assertRedirect();

    $token = DB::table('employees')->where('id', $employeeId)->value('invite_token');

    expect(DB::table('employees')->where('id', $employeeId)->value('access_status'))->toBe('invited')
        ->and($token)->not->toBeNull();

    $this->post("/employees/{$employeeId}/unlink-access")->assertRedirect();

    expect(DB::table('employees')->where('id', $employeeId)->value('user_id'))->toBeNull()
        ->and(DB::table('employees')->where('id', $employeeId)->value('access_status'))->toBe('not_invited')
        ->and(DB::table('employees')->where('id', $employeeId)->value('invite_token'))->toBeNull();
});

it('updates and deletes employees', function (): void {
    $employeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-002',
        'first_name' => 'Nina',
        'last_name' => 'Shah',
        'display_name' => 'Nina Shah',
        'work_email' => 'nina@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->put("/employees/{$employeeId}", [
        'employee_number' => 'EMP-002',
        'first_name' => 'Nina',
        'middle_name' => '',
        'last_name' => 'Shah',
        'display_name' => 'Nina S.',
        'work_email' => 'nina.s@example.com',
        'personal_email' => '',
        'employment_status' => 'inactive',
        'joined_on' => '',
    ])->assertRedirect();

    expect(DB::table('employees')->where('id', $employeeId)->value('display_name'))->toBe('Nina S.');

    $this->delete("/employees/{$employeeId}")->assertRedirect();

    expect(DB::table('employees')->where('id', $employeeId)->exists())->toBeFalse();
});
