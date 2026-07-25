<?php

declare(strict_types=1);

use Employeon\Attendance\Http\Controllers\AttendanceController;
use Employeon\Employees\Http\Controllers\EmployeeController;
use Employeon\Employees\Http\Controllers\EmployeeInvitationController;
use Employeon\Http\Controllers\AccessController;
use Employeon\Http\Middleware\EnsureEmployeonSession;
use Employeon\Support\ModuleRegistry;
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
            'name' => 'Employeon User',
            'email' => 'user@example.com',
            'role' => 'User',
        ];
    };

    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');

    Route::get('/employee-invitations/{token}', [EmployeeInvitationController::class, 'accept'])->name('employeon.employee-invitations.accept');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $email = is_string($credentials['email'] ?? null) ? $credentials['email'] : '';
        $name = trim(str_replace(['.', '_', '-'], ' ', strstr($email, '@', true) ?: $email));

        $request->session()->put('employeon.user', [
            'name' => $name !== '' ? ucwords($name) : 'Employeon User',
            'email' => $email,
            'role' => 'User',
        ]);

        return redirect()->route('employeon.profile');
    })->name('employeon.login');

    Route::middleware(EnsureEmployeonSession::class)->group(function () use ($userFromSession): void {
        Route::get('/', fn () => redirect()->route('employeon.profile'))->name('employeon.home');

        Route::get('/profile', function (Request $request) {
            return Inertia::render('Profile/Show', [
                'user' => $request->session()->get('employeon.user'),
            ]);
        })->name('employeon.profile');

        Route::post('/logout', function (Request $request) {
            $request->session()->forget('employeon.user');
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('employeon.logout');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employeon.employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employeon.employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employeon.employees.store');
        Route::post('/employees/views', [EmployeeController::class, 'storeView'])->name('employeon.employees.views.store');
        Route::put('/employees/views/{view}', [EmployeeController::class, 'updateView'])->name('employeon.employees.views.update');
        Route::delete('/employees/views/{view}', [EmployeeController::class, 'destroyView'])->name('employeon.employees.views.destroy');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employeon.employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employeon.employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employeon.employees.destroy');
        Route::post('/employees/{employee}/invite', [EmployeeController::class, 'invite'])->name('employeon.employees.invite');
        Route::post('/employees/{employee}/link-user', [EmployeeController::class, 'linkUser'])->name('employeon.employees.link-user');
        Route::post('/employees/{employee}/disable-access', [EmployeeController::class, 'disableAccess'])->name('employeon.employees.disable-access');
        Route::post('/employees/{employee}/unlink-access', [EmployeeController::class, 'unlinkAccess'])->name('employeon.employees.unlink-access');
        Route::post('/employees/{employee}/reset-invite', [EmployeeController::class, 'resetInvite'])->name('employeon.employees.reset-invite');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('employeon.attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('employeon.attendance.store');
        Route::put('/attendance/{entry}', [AttendanceController::class, 'update'])->name('employeon.attendance.update');
        Route::delete('/attendance/{entry}', [AttendanceController::class, 'destroy'])->name('employeon.attendance.destroy');

        Route::get('/roles-permissions', [AccessController::class, 'index'])->name('employeon.access.index');
        Route::post('/roles-permissions/roles', [AccessController::class, 'storeRole'])->name('employeon.access.roles.store');
        Route::put('/roles-permissions/roles/{role}', [AccessController::class, 'updateRole'])->name('employeon.access.roles.update');
        Route::delete('/roles-permissions/roles/{role}', [AccessController::class, 'destroyRole'])->name('employeon.access.roles.destroy');
        Route::put('/roles-permissions/roles/{role}/permissions', [AccessController::class, 'syncRolePermissions'])->name('employeon.access.roles.permissions');
        Route::post('/roles-permissions/permissions', [AccessController::class, 'storePermission'])->name('employeon.access.permissions.store');
        Route::put('/roles-permissions/permissions/{permission}', [AccessController::class, 'updatePermission'])->name('employeon.access.permissions.update');
        Route::delete('/roles-permissions/permissions/{permission}', [AccessController::class, 'destroyPermission'])->name('employeon.access.permissions.destroy');
        Route::put('/roles-permissions/users/{user}/roles', [AccessController::class, 'syncUserRoles'])->name('employeon.access.users.roles');

        Route::get('/{module}', function (Request $request, string $module) use ($userFromSession) {
            $module = app(ModuleRegistry::class)->findByPath($module);

            abort_if($module === null, 404);

            return Inertia::render($module->component, array_replace_recursive(
                ['user' => $userFromSession($request)],
                $module->inertiaProps($request),
            ));
        })->name('employeon.module');
    });
});
