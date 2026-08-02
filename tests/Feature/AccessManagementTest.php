<?php

declare(strict_types=1);

use Employeon\Tests\Fixtures\AccessUser;
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

    config()->set('employeon.access.user_model', AccessUser::class);

    Schema::dropIfExists('role_has_permissions');
    Schema::dropIfExists('model_has_roles');
    Schema::dropIfExists('model_has_permissions');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('permissions');
    Schema::dropIfExists('access_users');

    Schema::create('permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
        $table->unique(['name', 'guard_name']);
    });

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
        $table->unique(['name', 'guard_name']);
    });

    Schema::create('model_has_permissions', function (Blueprint $table): void {
        $table->unsignedBigInteger('permission_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->primary(['permission_id', 'model_id', 'model_type']);
    });

    Schema::create('model_has_roles', function (Blueprint $table): void {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->primary(['role_id', 'model_id', 'model_type']);
    });

    Schema::create('role_has_permissions', function (Blueprint $table): void {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
        $table->primary(['permission_id', 'role_id']);
    });

    Schema::create('access_users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    AccessUser::query()->create([
        'name' => 'Asha Admin',
        'email' => 'asha@example.com',
    ]);
});

it('creates roles and permissions and renders them on the access screen', function (): void {
    $this->withoutVite();

    $this->post('/roles-permissions/roles', [
        'name' => 'People Admin',
    ])->assertRedirect();

    $this->post('/roles-permissions/permissions', [
        'name' => 'employees.view',
    ])->assertRedirect();

    $this->get('/roles-permissions')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('RolesPermissions/Index')
            ->where('roles.0.name', 'People Admin')
            ->missing('roles.0.guard_name')
            ->where('permissions.0.name', 'employees.view')
            ->missing('permissions.0.guard_name')
            ->where('users.0.email', 'asha@example.com')
        );
});

it('maps permissions to roles and roles to users', function (): void {
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'People Admin',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'employees.manage',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = (int) AccessUser::query()->value('id');

    $this->put("/roles-permissions/roles/{$roleId}/permissions", [
        'permission_ids' => [$permissionId],
    ])->assertRedirect();

    $this->put("/roles-permissions/users/{$userId}/roles", [
        'role_ids' => [$roleId],
    ])->assertRedirect();

    expect(DB::table('role_has_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->exists())->toBeTrue()
        ->and(DB::table('model_has_roles')->where('role_id', $roleId)->where('model_id', $userId)->where('model_type', AccessUser::class)->exists())->toBeTrue();
});

it('updates and deletes roles and permissions', function (): void {
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'Old Role',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'old.permission',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->put("/roles-permissions/roles/{$roleId}", ['name' => 'New Role'])->assertRedirect();
    $this->put("/roles-permissions/permissions/{$permissionId}", ['name' => 'new.permission'])->assertRedirect();

    expect(DB::table('roles')->where('name', 'New Role')->exists())->toBeTrue()
        ->and(DB::table('permissions')->where('name', 'new.permission')->exists())->toBeTrue();

    $this->delete("/roles-permissions/roles/{$roleId}")->assertRedirect();
    $this->delete("/roles-permissions/permissions/{$permissionId}")->assertRedirect();

    expect(DB::table('roles')->where('id', $roleId)->exists())->toBeFalse()
        ->and(DB::table('permissions')->where('id', $permissionId)->exists())->toBeFalse();
});
