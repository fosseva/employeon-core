<?php

declare(strict_types=1);

namespace Employeon\Http\Controllers;

use Employeon\Support\AccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AccessController
{
    public function index(Request $request, AccessManager $accessManager): Response
    {
        return Inertia::render('RolesPermissions/Index', array_replace_recursive(
            [
                'user' => $this->userFromSession($request),
                'template' => [
                    'title' => 'Roles & Permissions',
                    'subtitle' => 'Manage roles, permissions, and user access',
                ],
            ],
            $accessManager->pageData(),
        ));
    }

    public function storeRole(Request $request, AccessManager $accessManager): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $accessManager->createRole($request->string('name')->toString());

        return back()->with('status', 'Role created.');
    }

    public function updateRole(Request $request, AccessManager $accessManager, int $role): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $accessManager->updateRole($role, $request->string('name')->toString());

        return back()->with('status', 'Role updated.');
    }

    public function destroyRole(AccessManager $accessManager, int $role): RedirectResponse
    {
        $accessManager->deleteRole($role);

        return back()->with('status', 'Role deleted.');
    }

    public function storePermission(Request $request, AccessManager $accessManager): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $accessManager->createPermission($request->string('name')->toString());

        return back()->with('status', 'Permission created.');
    }

    public function updatePermission(Request $request, AccessManager $accessManager, int $permission): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $accessManager->updatePermission($permission, $request->string('name')->toString());

        return back()->with('status', 'Permission updated.');
    }

    public function destroyPermission(AccessManager $accessManager, int $permission): RedirectResponse
    {
        $accessManager->deletePermission($permission);

        return back()->with('status', 'Permission deleted.');
    }

    public function syncRolePermissions(Request $request, AccessManager $accessManager, int $role): RedirectResponse
    {
        $request->validate([
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer'],
        ]);

        $accessManager->syncRolePermissions($role, $this->integerList($request->input('permission_ids', [])));

        return back()->with('status', 'Role permissions updated.');
    }

    public function syncUserRoles(Request $request, AccessManager $accessManager, int $user): RedirectResponse
    {
        $request->validate([
            'role_ids' => ['array'],
            'role_ids.*' => ['integer'],
        ]);

        $accessManager->syncUserRoles($user, $this->integerList($request->input('role_ids', [])));

        return back()->with('status', 'User roles updated.');
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

        return $integers;
    }

    private function stringValue(mixed $value, string $default = ''): string
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
