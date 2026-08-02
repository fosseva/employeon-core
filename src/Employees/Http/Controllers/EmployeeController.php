<?php

declare(strict_types=1);

namespace Employeon\Employees\Http\Controllers;

use Employeon\Employees\EmployeeManager;
use Employeon\Employees\EmployeeViewManager;
use Employeon\Support\SavedViewOwnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EmployeeController
{
    public function index(Request $request, EmployeeManager $employeeManager, EmployeeViewManager $employeeViewManager, SavedViewOwnerResolver $ownerResolver): Response
    {
        return Inertia::render('Employees/Index', array_replace_recursive(
            [
                'user' => $this->userFromSession($request),
                'template' => [
                    'title' => 'Employees',
                    'subtitle' => 'Employee directory and records',
                ],
                'views' => $employeeViewManager->views($ownerResolver->resolve($request)),
            ],
            $employeeManager->pageData(
                filterQuery: $request->string('filterQuery')->toString(),
                sorts: $this->sortList(json_decode($request->string('sorts')->toString(), true), [[
                    'column' => 'employee',
                    'direction' => 'asc',
                ]]),
                page: max(1, $this->integerValue($request->query('page'), 1)),
                perPage: $this->perPage($request),
            ),
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

    public function bulkDestroy(Request $request, EmployeeManager $employeeManager): RedirectResponse
    {
        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer'],
        ]);

        $employeeManager->deleteMany($this->integerList($validated['employee_ids'] ?? []));

        return back()->with('status', 'Employees deleted.');
    }

    public function storeView(Request $request, EmployeeViewManager $employeeViewManager, SavedViewOwnerResolver $ownerResolver): RedirectResponse
    {
        $employeeViewManager->create($ownerResolver->resolve($request), $this->viewPayload($request));

        return back()->with('status', 'Employee view created.');
    }

    public function updateView(Request $request, EmployeeViewManager $employeeViewManager, SavedViewOwnerResolver $ownerResolver, string $view): RedirectResponse
    {
        $employeeViewManager->save($ownerResolver->resolve($request), $view, $this->viewPayload($request));

        return back()->with('status', 'Employee view saved.');
    }

    public function destroyView(Request $request, EmployeeViewManager $employeeViewManager, SavedViewOwnerResolver $ownerResolver, string $view): RedirectResponse
    {
        $employeeViewManager->delete($ownerResolver->resolve($request), $view);

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
     * @return array{name: string, icon: string, filterQuery: string, sortColumn: string, sortDirection: string, sorts: array<int, array{column: string, direction: string}>, columns: array<int, string>}
     */
    private function viewPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'],
            'filterQuery' => ['nullable', 'string', 'max:1000'],
            'sortColumn' => ['required', 'string', 'max:255'],
            'sortDirection' => ['required', 'string', 'in:asc,desc'],
            'sorts' => ['nullable', 'array', 'max:3'],
            'sorts.*.column' => ['required_with:sorts', 'string', 'max:255'],
            'sorts.*.direction' => ['required_with:sorts', 'string', 'in:asc,desc'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'max:255'],
        ]);
        $sorts = $this->sortList($validated['sorts'] ?? null, [[
            'column' => $this->stringValue($validated['sortColumn'] ?? null, 'employee'),
            'direction' => $this->stringValue($validated['sortDirection'] ?? null, 'asc'),
        ]]);
        $primarySort = $sorts[0];

        return [
            'name' => $this->stringValue($validated['name'] ?? null, 'Employees'),
            'icon' => $this->stringValue($validated['icon'] ?? null, 'eye'),
            'filterQuery' => $this->stringValue($validated['filterQuery'] ?? null, ''),
            'sortColumn' => $primarySort['column'],
            'sortDirection' => $primarySort['direction'],
            'sorts' => $sorts,
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

    /**
     * @return array<int, int>
     */
    private function integerList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $integers = [];

        foreach ($value as $item) {
            if (is_numeric($item)) {
                $integers[] = (int) $item;
            }
        }

        return array_values(array_unique($integers));
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

    private function perPage(Request $request): int
    {
        $perPage = $this->integerValue($request->query('perPage'), 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }

    private function integerValue(mixed $value, int $default): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * @param  array<int, array{column: string, direction: string}>  $default
     * @return array<int, array{column: string, direction: string}>
     */
    private function sortList(mixed $value, array $default): array
    {
        if (! is_array($value)) {
            return $default;
        }

        $sorts = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $column = $this->stringValue($item['column'] ?? null, '');
            $direction = $this->stringValue($item['direction'] ?? null, 'asc');

            if ($column === '') {
                continue;
            }

            $sorts[] = [
                'column' => $column,
                'direction' => $direction === 'desc' ? 'desc' : 'asc',
            ];
        }

        return $sorts !== [] ? $sorts : $default;
    }
}
