<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('web')->group(function (): void {
    $userFromSession = function (Request $request): array {
        $user = $request->session()->get('employeon.user');

        if (is_array($user)) {
            return $user;
        }

        return [
            'name' => 'Aniket Magadum',
            'email' => 'aniket@example.com',
            'role' => 'People Operations Admin',
        ];
    };

    $renderModulePage = fn (Request $request, string $title, string $subtitle, string $component) => Inertia::render($component, [
        'user' => $userFromSession($request),
        'template' => [
            'title' => $title,
            'subtitle' => $subtitle,
        ],
    ]);

    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $request->session()->put('employeon.user', [
            'name' => 'Aniket Magadum',
            'email' => $credentials['email'],
            'role' => 'People Operations Admin',
        ]);

        return redirect()->route('employeon.profile');
    })->name('employeon.login');

    Route::get('/', fn () => redirect()->route('employeon.profile'))->name('employeon.home');

    Route::get('/dashboard', fn (Request $request) => $renderModulePage($request, 'Dashboard', 'Workforce command center', 'Dashboard/Index'))->name('employeon.dashboard');
    Route::get('/employees', fn (Request $request) => $renderModulePage($request, 'Employees', 'Employee directory and records', 'Employees/Index'))->name('employeon.employees');
    Route::get('/departments', fn (Request $request) => $renderModulePage($request, 'Departments', 'Team structure and reporting lines', 'Departments/Index'))->name('employeon.departments');
    Route::get('/attendance', fn (Request $request) => $renderModulePage($request, 'Attendance', 'Daily presence and time tracking', 'Attendance/Index'))->name('employeon.attendance');
    Route::get('/leaves', fn (Request $request) => $renderModulePage($request, 'Leaves', 'Leave requests and approvals', 'Leaves/Index'))->name('employeon.leaves');
    Route::get('/payroll', fn (Request $request) => $renderModulePage($request, 'Payroll', 'Payroll cycles and compensation reviews', 'Payroll/Index'))->name('employeon.payroll');
    Route::get('/compliance', fn (Request $request) => $renderModulePage($request, 'Compliance', 'Policy, document, and audit readiness', 'Compliance/Index'))->name('employeon.compliance');
    Route::get('/reports', fn (Request $request) => $renderModulePage($request, 'Reports', 'Operational reports and export templates', 'Reports/Index'))->name('employeon.reports');
    Route::get('/settings', fn (Request $request) => $renderModulePage($request, 'Settings', 'Workspace preferences and system controls', 'Settings/Index'))->name('employeon.settings');
    Route::get('/support', fn (Request $request) => $renderModulePage($request, 'Support', 'Help desk and implementation guidance', 'Support/Index'))->name('employeon.support');

    Route::get('/profile', function (Request $request) {
        $user = $request->session()->get('employeon.user');

        if (! is_array($user)) {
            return redirect()->route('login');
        }

        return Inertia::render('Profile/Show', [
            'user' => $user,
        ]);
    })->name('employeon.profile');

    Route::post('/logout', function (Request $request) {
        $request->session()->forget('employeon.user');
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('employeon.logout');
});
