<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('renders the login page', function (): void {
    $this->withoutVite();

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/Login')
        );
});

it('logs in with a sample session and renders the profile page', function (): void {
    $this->withoutVite();

    $this->post('/login', [
        'email' => 'aniket@example.com',
        'password' => 'password',
        'remember' => true,
    ])->assertRedirect('/profile');

    $this->get('/profile')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Profile/Show')
            ->where('user.email', 'aniket@example.com')
        );
});

it('logs out of the sample session', function (): void {
    $this->post('/login', [
        'email' => 'aniket@example.com',
        'password' => 'password',
    ])->assertRedirect('/profile');

    $this->post('/logout')->assertRedirect('/login');

    $this->get('/profile')->assertRedirect('/login');
});

it('redirects protected modules to login without a session', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/employees')->assertRedirect('/login');
    $this->get('/attendance')->assertRedirect('/login');
    $this->get('/roles-permissions')->assertRedirect('/login');
});

it('renders registered modules through the shared module template', function (string $path, string $title): void {
    $this->withoutVite();

    $this->withSession([
        'employeon.user' => [
            'name' => 'Aniket Magadum',
            'email' => 'aniket@example.com',
            'role' => 'People Operations Admin',
        ],
    ]);

    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ModuleTemplate')
            ->where('template.title', $title)
            ->has('user.email')
        );
})->with([
    ['/dashboard', 'Dashboard'],
    ['/departments', 'Departments'],
    ['/leaves', 'Leaves'],
    ['/payroll', 'Payroll'],
    ['/compliance', 'Compliance'],
    ['/reports', 'Reports'],
    ['/settings', 'Settings'],
    ['/support', 'Support'],
]);

it('renders employees and attendance as first party module pages', function (string $path, string $component, string $title): void {
    $this->withoutVite();

    $this->withSession([
        'employeon.user' => [
            'name' => 'Aniket Magadum',
            'email' => 'aniket@example.com',
            'role' => 'People Operations Admin',
        ],
    ]);

    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component($component)
            ->where('template.title', $title)
        );
})->with([
    ['/employees', 'Employees/Index', 'Employees'],
    ['/attendance', 'Attendance/Index', 'Attendance'],
]);

it('renders roles and permissions without exposing backend configuration', function (): void {
    $this->withoutVite();

    $this->withSession([
        'employeon.user' => [
            'name' => 'Aniket Magadum',
            'email' => 'aniket@example.com',
            'role' => 'People Operations Admin',
        ],
    ]);

    $this->get('/roles-permissions')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('RolesPermissions/Index')
            ->missing('database')
            ->missing('authorization')
            ->missing('roles.0.guard_name')
            ->missing('permissions.0.guard_name')
        );
});
