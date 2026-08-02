<?php

declare(strict_types=1);

use Employeon\Employees\Database\Seeders\EmployeeSeeder;
use Employeon\Employees\Events\EmployeeInvited;
use Employeon\Employees\Notifications\EmployeeInvitationNotification;
use Employeon\Tests\Fixtures\AccessUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    Schema::dropIfExists('attendance_entries');
    Schema::dropIfExists('saved_views');
    Schema::dropIfExists('access_users');
    Schema::dropIfExists('employees');

    config()->set('employeon.access.user_model', AccessUser::class);
    config()->set('auth.providers.users.model', AccessUser::class);

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

    $adminUserId = DB::table('access_users')->insertGetId([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $adminUser = AccessUser::query()->findOrFail($adminUserId);

    $this->actingAs($adminUser);

    $this->withSession([
        'employeon.user' => [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ],
    ]);

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

    Schema::create('saved_views', function (Blueprint $table): void {
        $table->id();
        $table->string('owner_type');
        $table->unsignedBigInteger('owner_id');
        $table->string('viewable_type');
        $table->string('key');
        $table->string('name');
        $table->string('icon')->default('eye');
        $table->text('filter_query')->nullable();
        $table->string('sort_column')->default('employee');
        $table->string('sort_direction')->default('asc');
        $table->json('sorts')->nullable();
        $table->json('columns');
        $table->timestamps();
        $table->index(['owner_type', 'owner_id']);
        $table->index('viewable_type');
        $table->unique(['owner_type', 'owner_id', 'viewable_type', 'key']);
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
        'filterQuery' => 'audit employment:active',
        'sortColumn' => 'work_email',
        'sortDirection' => 'desc',
        'sorts' => [
            ['column' => 'work_email', 'direction' => 'desc'],
            ['column' => 'employee', 'direction' => 'asc'],
        ],
        'columns' => ['employee', 'work_email', 'joined_on'],
    ])->assertRedirect();

    $view = DB::table('saved_views')->where('name', 'Work email audit')->first();
    $ownerId = DB::table('access_users')->where('email', 'admin@example.com')->value('id');

    expect($view)->not->toBeNull()
        ->and($view?->owner_type)->toBe(AccessUser::class)
        ->and($view?->owner_id)->toBe($ownerId)
        ->and($view?->viewable_type)->toBe('employees')
        ->and($view?->icon)->toBe('mail')
        ->and($view?->filter_query)->toBe('audit employment:active')
        ->and($view?->sort_column)->toBe('work_email')
        ->and(json_decode((string) $view?->sorts, true))->toBe([
            ['column' => 'work_email', 'direction' => 'desc'],
            ['column' => 'employee', 'direction' => 'asc'],
        ]);

    $this->get('/employees')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Employees/Index')
            ->where('views.4.name', 'Work email audit')
            ->where('views.4.icon', 'mail')
            ->where('views.4.filterQuery', 'audit employment:active')
            ->where('views.4.sortColumn', 'work_email')
            ->where('views.4.sorts.1.column', 'employee')
            ->where('views.4.columns.1', 'work_email')
        );

    $this->put('/employees/views/'.$view?->key, [
        'name' => 'Updated audit',
        'icon' => 'shield',
        'filterQuery' => 'access:invited',
        'sortColumn' => 'employee',
        'sortDirection' => 'asc',
        'sorts' => [
            ['column' => 'employee', 'direction' => 'asc'],
        ],
        'columns' => ['employee', 'access_status'],
    ])->assertRedirect();

    expect(DB::table('saved_views')->where('id', $view?->id)->value('name'))->toBe('Updated audit');
    expect(DB::table('saved_views')->where('id', $view?->id)->value('filter_query'))->toBe('access:invited');

    $this->delete('/employees/views/'.$view?->key)->assertRedirect();

    expect(DB::table('saved_views')->where('id', $view?->id)->exists())->toBeFalse();
});

it('does not load saved views for other viewable types on the employees page', function (): void {
    $this->withoutVite();

    $ownerId = DB::table('access_users')->where('email', 'admin@example.com')->value('id');

    DB::table('saved_views')->insert([
        'owner_type' => AccessUser::class,
        'owner_id' => $ownerId,
        'viewable_type' => 'attendance',
        'key' => 'custom-attendance',
        'name' => 'Attendance audit',
        'icon' => 'clock',
        'filter_query' => 'status:absent',
        'sort_column' => 'attendance_date',
        'sort_direction' => 'desc',
        'sorts' => json_encode([['column' => 'attendance_date', 'direction' => 'desc']]),
        'columns' => json_encode(['employee', 'attendance_date']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get('/employees')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Employees/Index')
            ->where('views.0.name', 'All employees')
            ->missing('views.4')
        );
});

it('persists employee views after using the starter login flow', function (): void {
    $this->withoutVite();

    $this->post('/logout')->assertRedirect('/login');

    $this->post('/login', [
        'email' => 'starter@example.com',
        'password' => 'password',
    ])->assertRedirect('/profile');

    $this->post('/employees/views', [
        'name' => 'Starter login view',
        'icon' => 'star',
        'filterQuery' => 'employment:active',
        'sortColumn' => 'employee',
        'sortDirection' => 'asc',
        'sorts' => [
            ['column' => 'employee', 'direction' => 'asc'],
        ],
        'columns' => ['employee', 'work_email'],
    ])->assertRedirect();

    $ownerId = DB::table('access_users')->where('email', 'starter@example.com')->value('id');
    $view = DB::table('saved_views')->where('name', 'Starter login view')->first();

    expect($ownerId)->not->toBeNull()
        ->and($view)->not->toBeNull()
        ->and($view?->owner_type)->toBe(AccessUser::class)
        ->and($view?->owner_id)->toBe($ownerId)
        ->and($view?->viewable_type)->toBe('employees');
});

it('persists employee views with a client generated custom key', function (): void {
    $this->withoutVite();

    $this->post('/login', [
        'email' => 'starter-key@example.com',
        'password' => 'password',
    ])->assertRedirect('/profile');

    $this->put('/employees/views/custom-client-key', [
        'name' => 'Client key view',
        'icon' => 'star',
        'filterQuery' => '',
        'sortColumn' => 'employee',
        'sortDirection' => 'asc',
        'sorts' => [
            ['column' => 'employee', 'direction' => 'asc'],
        ],
        'columns' => ['employee', 'work_email'],
    ])->assertRedirect();

    $ownerId = DB::table('access_users')->where('email', 'starter-key@example.com')->value('id');

    expect(DB::table('saved_views')
        ->where('owner_type', AccessUser::class)
        ->where('owner_id', $ownerId)
        ->where('viewable_type', 'employees')
        ->where('key', 'custom-client-key')
        ->where('name', 'Client key view')
        ->exists())->toBeTrue();
});

it('persists employee views when the session contains a real user id', function (): void {
    $this->withoutVite();

    Auth::logout();

    $ownerId = DB::table('access_users')->where('email', 'admin@example.com')->value('id');

    $this->withSession([
        'employeon.user' => [
            'user_id' => $ownerId,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ],
    ]);

    $this->put('/employees/views/custom-session-owner', [
        'name' => 'Session owner view',
        'icon' => 'star',
        'filterQuery' => '',
        'sortColumn' => 'employee',
        'sortDirection' => 'asc',
        'sorts' => [
            ['column' => 'employee', 'direction' => 'asc'],
        ],
        'columns' => ['employee', 'work_email'],
    ])->assertRedirect();

    expect(DB::table('saved_views')
        ->where('owner_type', AccessUser::class)
        ->where('owner_id', $ownerId)
        ->where('viewable_type', 'employees')
        ->where('key', 'custom-session-owner')
        ->exists())->toBeTrue();
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

it('bulk deletes selected employees and their attendance entries', function (): void {
    $firstEmployeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-101',
        'first_name' => 'Asha',
        'last_name' => 'Rao',
        'display_name' => 'Asha Rao',
        'work_email' => 'asha.bulk@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $secondEmployeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-102',
        'first_name' => 'Rohan',
        'last_name' => 'Mehta',
        'display_name' => 'Rohan Mehta',
        'work_email' => 'rohan.bulk@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $keptEmployeeId = DB::table('employees')->insertGetId([
        'employee_number' => 'EMP-103',
        'first_name' => 'Nina',
        'last_name' => 'Shah',
        'display_name' => 'Nina Shah',
        'work_email' => 'nina.bulk@example.com',
        'employment_status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('attendance_entries')->insert([
        [
            'employee_id' => $firstEmployeeId,
            'attendance_date' => '2026-08-01',
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'employee_id' => $keptEmployeeId,
            'attendance_date' => '2026-08-01',
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->post('/employees/bulk-delete', [
        'employee_ids' => [$firstEmployeeId, $secondEmployeeId],
    ])->assertRedirect();

    expect(DB::table('employees')->whereIn('id', [$firstEmployeeId, $secondEmployeeId])->exists())->toBeFalse()
        ->and(DB::table('employees')->where('id', $keptEmployeeId)->exists())->toBeTrue()
        ->and(DB::table('attendance_entries')->where('employee_id', $firstEmployeeId)->exists())->toBeFalse()
        ->and(DB::table('attendance_entries')->where('employee_id', $keptEmployeeId)->exists())->toBeTrue();
});

it('seeds one thousand employees across employment and access stages', function (): void {
    $this->seed(EmployeeSeeder::class);

    expect(DB::table('employees')->count())->toBe(1000)
        ->and(DB::table('employees')->where('employment_status', 'active')->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('employment_status', 'inactive')->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('employment_status', 'on_leave')->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('access_status', 'not_invited')->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('access_status', 'invited')->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('access_status', 'active')->count())->toBeGreaterThan(0)
        ->and(DB::table('employees')->where('access_status', 'disabled')->count())->toBeGreaterThan(0);
});
