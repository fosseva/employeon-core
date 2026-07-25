import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle2, KeyRound, Plus, Save, ShieldCheck, Trash2, UsersRound } from 'lucide-react';
import AppLayout from '../../Layouts/AppLayout';

type User = {
  name: string;
  email: string;
  role: string;
};

type Template = {
  title: string;
  subtitle: string;
};

type Role = {
  id: number;
  name: string;
  permission_ids: number[];
  user_count: number;
};

type Permission = {
  id: number;
  name: string;
  role_ids: number[];
};

type AssignableUser = {
  id: number;
  name: string;
  email: string;
  role_ids: number[];
};

type RolesPermissionsPageProps = {
  user: User;
  template: Template;
  roles: Role[];
  permissions: Permission[];
  users: AssignableUser[];
};

function toggleId(ids: number[], id: number) {
  return ids.includes(id) ? ids.filter((value) => value !== id) : [...ids, id];
}

function postDelete(url: string) {
  if (window.confirm('Delete this record?')) {
    router.post(url, { _method: 'DELETE' }, { preserveScroll: true });
  }
}

function RoleCreateForm() {
  const form = useForm({ name: '' });

  return (
    <form
      className="flex flex-col gap-2 sm:flex-row"
      onSubmit={(event) => {
        event.preventDefault();
        form.post('/roles-permissions/roles', {
          preserveScroll: true,
          onSuccess: () => form.reset(),
        });
      }}
    >
      <input
        className="h-10 min-w-0 flex-1 rounded-md border border-[#17201b]/10 bg-[#fffffb] px-3 text-sm font-semibold text-[#17201b] outline-none focus:border-[#27615a]/50"
        value={form.data.name}
        placeholder="Role name"
        onChange={(event) => form.setData('name', event.target.value)}
      />
      <button
        className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-70"
        type="submit"
        disabled={form.processing}
      >
        <Plus className="size-4" />
        Add Role
      </button>
    </form>
  );
}

function PermissionCreateForm() {
  const form = useForm({ name: '' });

  return (
    <form
      className="flex flex-col gap-2 sm:flex-row"
      onSubmit={(event) => {
        event.preventDefault();
        form.post('/roles-permissions/permissions', {
          preserveScroll: true,
          onSuccess: () => form.reset(),
        });
      }}
    >
      <input
        className="h-10 min-w-0 flex-1 rounded-md border border-[#17201b]/10 bg-[#fffffb] px-3 text-sm font-semibold text-[#17201b] outline-none focus:border-[#27615a]/50"
        value={form.data.name}
        placeholder="Permission name, e.g. employees.view"
        onChange={(event) => form.setData('name', event.target.value)}
      />
      <button
        className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-70"
        type="submit"
        disabled={form.processing}
      >
        <Plus className="size-4" />
        Add Permission
      </button>
    </form>
  );
}

function EditableRole({ permissions, role }: { permissions: Permission[]; role: Role }) {
  const detailsForm = useForm({ name: role.name });
  const permissionsForm = useForm({ permission_ids: role.permission_ids });

  return (
    <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4">
      <form
        className="flex flex-col gap-2 sm:flex-row"
        onSubmit={(event) => {
          event.preventDefault();
          detailsForm.transform((data) => ({ ...data, _method: 'PUT' }));
          detailsForm.post(`/roles-permissions/roles/${role.id}`, { preserveScroll: true });
        }}
      >
        <input
          className="h-9 min-w-0 flex-1 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 text-sm font-bold text-[#17201b] outline-none focus:border-[#27615a]/50"
          value={detailsForm.data.name}
          onChange={(event) => detailsForm.setData('name', event.target.value)}
        />
        <button
          className="grid size-9 place-items-center rounded-md border border-[#17201b]/10 text-[#27615a] hover:bg-[#f7faf4] disabled:opacity-70"
          type="submit"
          title="Save role"
          disabled={detailsForm.processing}
        >
          <Save className="size-4" />
        </button>
        <button
          className="grid size-9 place-items-center rounded-md border border-[#17201b]/10 text-[#a33b2f] hover:bg-[#fff4f1]"
          type="button"
          title="Delete role"
          onClick={() => postDelete(`/roles-permissions/roles/${role.id}`)}
        >
          <Trash2 className="size-4" />
        </button>
      </form>

      <div className="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-[#59635d]">
        <span>{role.user_count} users</span>
        <span>{permissionsForm.data.permission_ids.length} permissions</span>
      </div>

      <form
        className="mt-4 grid gap-2"
        onSubmit={(event) => {
          event.preventDefault();
          permissionsForm.transform((data) => ({ ...data, _method: 'PUT' }));
          permissionsForm.post(`/roles-permissions/roles/${role.id}/permissions`, {
            preserveScroll: true,
          });
        }}
      >
        <div className="grid max-h-52 gap-2 overflow-y-auto pr-1">
          {permissions.length > 0 ? (
            permissions.map((permission) => (
              <label
                className="flex items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 py-2 text-xs font-bold text-[#34423a]"
                key={permission.id}
              >
                <input
                  className="size-4 accent-[#27615a]"
                  type="checkbox"
                  checked={permissionsForm.data.permission_ids.includes(permission.id)}
                  onChange={() =>
                    permissionsForm.setData(
                      'permission_ids',
                      toggleId(permissionsForm.data.permission_ids, permission.id),
                    )
                  }
                />
                {permission.name}
              </label>
            ))
          ) : (
            <p className="rounded-md border border-dashed border-[#17201b]/15 px-3 py-4 text-xs font-semibold text-[#59635d]">
              Create permissions before mapping them to roles.
            </p>
          )}
        </div>
        <button
          className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-[#27615a]/30 bg-[#f7faf4] px-3 text-xs font-black text-[#27615a] disabled:opacity-70"
          type="submit"
          disabled={permissionsForm.processing}
        >
          <CheckCircle2 className="size-4" />
          Save Permissions
        </button>
      </form>
    </div>
  );
}

function EditablePermission({ permission }: { permission: Permission }) {
  const form = useForm({ name: permission.name });

  return (
    <form
      className="flex items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#fffffb] p-2"
      onSubmit={(event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, _method: 'PUT' }));
        form.post(`/roles-permissions/permissions/${permission.id}`, { preserveScroll: true });
      }}
    >
      <input
        className="h-9 min-w-0 flex-1 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 text-xs font-bold text-[#17201b] outline-none focus:border-[#27615a]/50"
        value={form.data.name}
        onChange={(event) => form.setData('name', event.target.value)}
      />
      <span className="hidden text-xs font-bold text-[#59635d] sm:inline">
        {permission.role_ids.length} roles
      </span>
      <button
        className="grid size-9 place-items-center rounded-md border border-[#17201b]/10 text-[#27615a] hover:bg-[#f7faf4] disabled:opacity-70"
        type="submit"
        title="Save permission"
        disabled={form.processing}
      >
        <Save className="size-4" />
      </button>
      <button
        className="grid size-9 place-items-center rounded-md border border-[#17201b]/10 text-[#a33b2f] hover:bg-[#fff4f1]"
        type="button"
        title="Delete permission"
        onClick={() => postDelete(`/roles-permissions/permissions/${permission.id}`)}
      >
        <Trash2 className="size-4" />
      </button>
    </form>
  );
}

function UserRoleAssignment({ roles, user }: { roles: Role[]; user: AssignableUser }) {
  const form = useForm({ role_ids: user.role_ids });

  return (
    <form
      className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4"
      onSubmit={(event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, _method: 'PUT' }));
        form.post(`/roles-permissions/users/${user.id}/roles`, { preserveScroll: true });
      }}
    >
      <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <p className="truncate text-sm font-black text-[#17201b]">{user.name}</p>
          <p className="truncate text-xs font-semibold text-[#59635d]">{user.email}</p>
        </div>
        <button
          className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-[#27615a]/30 bg-[#f7faf4] px-3 text-xs font-black text-[#27615a] disabled:opacity-70"
          type="submit"
          disabled={form.processing}
        >
          <Save className="size-4" />
          Save
        </button>
      </div>

      <div className="mt-3 flex flex-wrap gap-2">
        {roles.map((role) => (
          <label
            className="flex items-center gap-2 rounded border border-[#17201b]/10 bg-[#f7faf4] px-2 py-1.5 text-xs font-bold text-[#34423a]"
            key={role.id}
          >
            <input
              className="size-4 accent-[#27615a]"
              type="checkbox"
              checked={form.data.role_ids.includes(role.id)}
              onChange={() => form.setData('role_ids', toggleId(form.data.role_ids, role.id))}
            />
            {role.name}
          </label>
        ))}
      </div>
    </form>
  );
}

export default function RolesPermissionsIndex({
  user,
  template,
  roles,
  permissions,
  users,
}: RolesPermissionsPageProps) {
  return (
    <>
      <Head title={template.title} />

      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="grid gap-5">
          <section className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5 shadow-[0_18px_45px_rgba(23,32,27,0.08)]">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
              <div className="max-w-3xl">
                <p className="text-xs font-black uppercase text-[#27615a]">
                  Spatie permissions
                </p>
                <h2 className="mt-1 text-2xl font-black text-[#17201b]">
                  Core access control
                </h2>
                <p className="mt-2 text-sm leading-6 text-[#59635d]">
                  Create roles and permissions, map permissions to roles, and assign roles to
                  users from the application database.
                </p>
              </div>

              <div className="grid min-w-[220px] gap-2 rounded-lg border border-[#17201b]/10 bg-[#f7faf4] p-3">
                <p className="text-xs font-black uppercase text-[#7a5a3a]">Access summary</p>
                <p className="text-2xl font-black text-[#17201b]">{roles.length + permissions.length}</p>
                <p className="text-xs font-semibold text-[#59635d]">
                  Roles and permissions configured
                </p>
              </div>
            </div>
          </section>

          <section className="grid gap-4 md:grid-cols-3">
            <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4">
              <ShieldCheck className="size-5 text-[#27615a]" />
              <p className="mt-3 text-2xl font-black text-[#17201b]">{roles.length}</p>
              <p className="text-xs font-bold uppercase text-[#7a5a3a]">Roles</p>
            </div>
            <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4">
              <KeyRound className="size-5 text-[#27615a]" />
              <p className="mt-3 text-2xl font-black text-[#17201b]">{permissions.length}</p>
              <p className="text-xs font-bold uppercase text-[#7a5a3a]">Permissions</p>
            </div>
            <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4">
              <UsersRound className="size-5 text-[#27615a]" />
              <p className="mt-3 text-2xl font-black text-[#17201b]">{users.length}</p>
              <p className="text-xs font-bold uppercase text-[#7a5a3a]">Assignable users</p>
            </div>
          </section>

          <section className="grid gap-5 xl:grid-cols-[1fr_420px]">
            <div className="grid gap-5">
              <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5">
                <div className="mb-4">
                  <p className="text-sm font-black text-[#17201b]">Roles</p>
                  <p className="mt-1 text-xs text-[#59635d]">
                    Save permissions onto each role after selecting the checkboxes.
                  </p>
                </div>
                <RoleCreateForm />
                <div className="mt-4 grid gap-3">
                  {roles.map((role) => (
                    <EditableRole key={role.id} permissions={permissions} role={role} />
                  ))}
                  {roles.length === 0 && (
                    <p className="rounded-lg border border-dashed border-[#17201b]/15 px-4 py-8 text-center text-sm font-semibold text-[#59635d]">
                      No roles yet.
                    </p>
                  )}
                </div>
              </div>

              <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5">
                <div className="mb-4">
                  <p className="text-sm font-black text-[#17201b]">User role assignment</p>
                  <p className="mt-1 text-xs text-[#59635d]">
                    Assign roles to application users who should receive access.
                  </p>
                </div>
                <div className="grid gap-3">
                  {users.map((assignableUser) => (
                    <UserRoleAssignment key={assignableUser.id} roles={roles} user={assignableUser} />
                  ))}
                  {users.length === 0 && (
                    <p className="rounded-lg border border-dashed border-[#17201b]/15 px-4 py-8 text-center text-sm font-semibold text-[#59635d]">
                      No assignable users are available yet.
                    </p>
                  )}
                </div>
              </div>
            </div>

            <aside className="grid content-start gap-5">
              <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5">
                <div className="mb-4">
                  <p className="text-sm font-black text-[#17201b]">Permissions</p>
                  <p className="mt-1 text-xs text-[#59635d]">
                    Use dotted names so modules can check abilities consistently.
                  </p>
                </div>
                <PermissionCreateForm />
                <div className="mt-4 grid gap-2">
                  {permissions.map((permission) => (
                    <EditablePermission key={permission.id} permission={permission} />
                  ))}
                  {permissions.length === 0 && (
                    <p className="rounded-lg border border-dashed border-[#17201b]/15 px-4 py-8 text-center text-sm font-semibold text-[#59635d]">
                      No permissions yet.
                    </p>
                  )}
                </div>
              </div>
            </aside>
          </section>
        </div>
      </AppLayout>
    </>
  );
}
