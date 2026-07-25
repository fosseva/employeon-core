<?php

declare(strict_types=1);

namespace Employeon\Employees\Http\Controllers;

use Employeon\Employees\EmployeeManager;
use Employeon\Employees\EmployeeViewManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EmployeeController
{
    public function index(Request $request, EmployeeManager $employeeManager, EmployeeViewManager $employeeViewManager): Response
    {
        return Inertia::render('Employees/Index', array_replace_recursive(
            [
                'user' => $this->userFromSession($request),
                'template' => [
                    'title' => 'Employees',
                    'subtitle' => 'Employee directory and records',
                ],
                'views' => $employeeViewManager->views($this->ownerEmail($request)),
            ],
            $employeeManager->pageData(),
        ));
    }

    public function create(Request $request, EmployeeManager $employeeManager): Response
    {
        return Inertia::render('Employees/Form', [
            'user' => $this->userFromSession($request),
            'template' => [
                'title' => 'Add Employee',
                'subtitle' => 'Create an employee record',
            ],
            'employee' => null,
            'access' => [
                'can_manage_users' => $employeeManager->canManageUsers(),
            ],
        ]);
    }

    public function edit(Request $request, EmployeeManager $employeeManager, int $employee): Response|RedirectResponse
    {
        $employeeData = $employeeManager->find($employee);

        if ($employeeData === null) {
            return redirect()->route('employeon.employees.index');
        }

        return Inertia::render('Employees/Form', [
            'user' => $this->userFromSession($request),
            'template' => [
                'title' => 'Edit Employee',
                'subtitle' => 'Update employee details and login access',
            ],
            'employee' => $employeeData,
            'access' => [
                'can_manage_users' => $employeeManager->canManageUsers(),
            ],
        ]);
    }

    public function store(Request $request, EmployeeManager $employeeManager): RedirectResponse
    {
        $request->validate($this->rules());

        $employeeManager->create($this->payload($request));

        return redirect()->route('employeon.employees.index')->with('status', 'Employee created.');
    }

    public function update(Request $request, EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $request->validate($this->rules());

        $employeeManager->update($employee, $this->payload($request));

        return redirect()->route('employeon.employees.index')->with('status', 'Employee updated.');
    }

    public function destroy(EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $employeeManager->delete($employee);

        return back()->with('status', 'Employee deleted.');
    }

    public function storeView(Request $request, EmployeeViewManager $employeeViewManager): RedirectResponse
    {
        $employeeViewManager->create($this->ownerEmail($request), $this->viewPayload($request));

        return back()->with('status', 'Employee view created.');
    }

    public function updateView(Request $request, EmployeeViewManager $employeeViewManager, string $view): RedirectResponse
    {
        $employeeViewManager->save($this->ownerEmail($request), $view, $this->viewPayload($request));

        return back()->with('status', 'Employee view saved.');
    }

    public function destroyView(Request $request, EmployeeViewManager $employeeViewManager, string $view): RedirectResponse
    {
        $employeeViewManager->delete($this->ownerEmail($request), $view);

        return back()->with('status', 'Employee view deleted.');
    }

    public function invite(EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $employeeManager->invite($employee);

        return back()->with('status', 'Employee invited.');
    }

    public function linkUser(Request $request, EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $userId = $employeeManager->linkExistingUser($employee, $request->string('email')->toString());

        if ($userId === null) {
            return back()->withErrors([
                'email' => 'No user account was found for this email.',
            ]);
        }

        return back()->with('status', 'Employee linked to user.');
    }

    public function disableAccess(EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $employeeManager->disableAccess($employee);

        return back()->with('status', 'Employee access disabled.');
    }

    public function unlinkAccess(EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $employeeManager->unlinkAccess($employee);

        return back()->with('status', 'Employee access unlinked.');
    }

    public function resetInvite(EmployeeManager $employeeManager, int $employee): RedirectResponse
    {
        $employeeManager->resetInvite($employee);

        return back()->with('status', 'Employee invite reset.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'employee_number' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'work_email' => ['nullable', 'email', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['required', 'string', 'in:active,inactive,on_leave'],
            'joined_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function payload(Request $request): array
    {
        return [
            'employee_number' => $request->string('employee_number')->toString(),
            'first_name' => $request->string('first_name')->toString(),
            'middle_name' => $request->string('middle_name')->toString(),
            'last_name' => $request->string('last_name')->toString(),
            'display_name' => $request->string('display_name')->toString(),
            'work_email' => $request->string('work_email')->toString(),
            'personal_email' => $request->string('personal_email')->toString(),
            'employment_status' => $request->string('employment_status')->toString(),
            'joined_on' => $request->string('joined_on')->toString(),
        ];
    }

    /**
     * @return array{name: string, icon: string, search: string, employmentStatus: string, accessStatus: string, sortColumn: string, sortDirection: string, columns: array<int, string>}
     */
    private function viewPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'employmentStatus' => ['required', 'string', 'max:255'],
            'accessStatus' => ['required', 'string', 'max:255'],
            'sortColumn' => ['required', 'string', 'max:255'],
            'sortDirection' => ['required', 'string', 'in:asc,desc'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'max:255'],
        ]);

        return [
            'name' => $this->stringValue($validated['name'] ?? null, 'Employees'),
            'icon' => $this->stringValue($validated['icon'] ?? null, 'eye'),
            'search' => $this->stringValue($validated['search'] ?? null, ''),
            'employmentStatus' => $this->stringValue($validated['employmentStatus'] ?? null, 'all'),
            'accessStatus' => $this->stringValue($validated['accessStatus'] ?? null, 'all'),
            'sortColumn' => $this->stringValue($validated['sortColumn'] ?? null, 'employee'),
            'sortDirection' => $this->stringValue($validated['sortDirection'] ?? null, 'asc'),
            'columns' => $this->stringList($validated['columns'] ?? []),
        ];
    }

    /**
     * @return array{name: string, email: string, role: string}
     */
    private function userFromSession(Request $request): array
    {
        $user = $request->session()->get('employeon.user');

        if (is_array($user)) {
            return [
                'name' => $this->stringValue($user['name'] ?? null, 'Employeon Admin'),
                'email' => $this->stringValue($user['email'] ?? null, 'admin@example.com'),
                'role' => $this->stringValue($user['role'] ?? null, 'Admin'),
            ];
        }

        return [
            'name' => 'Employeon Admin',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ];
    }

    private function ownerEmail(Request $request): string
    {
        $user = $this->userFromSession($request);

        return $user['email'];
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }

        return array_values(array_unique($strings));
    }

    private function stringValue(mixed $value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return $default;
    }
}
