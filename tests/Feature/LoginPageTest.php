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

it('renders dummy templates for sidebar navigation pages', function (string $path, string $component): void {
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
            ->has('template.title')
            ->has('user.email')
        );
})->with([
    ['/dashboard', 'Dashboard/Index'],
    ['/employees', 'Employees/Index'],
    ['/departments', 'Departments/Index'],
    ['/attendance', 'Attendance/Index'],
    ['/leaves', 'Leaves/Index'],
    ['/payroll', 'Payroll/Index'],
    ['/compliance', 'Compliance/Index'],
    ['/reports', 'Reports/Index'],
    ['/settings', 'Settings/Index'],
    ['/support', 'Support/Index'],
]);
