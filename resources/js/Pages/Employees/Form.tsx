import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Link2, Plus, RotateCcw, Save, Send, ShieldOff, Unlink } from 'lucide-react';
import { type ReactNode } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type User = { name: string; email: string; role: string };
type Template = { title: string; subtitle: string };
type Employee = {
  id: number;
  user_id: number | null;
  employee_number: string;
  first_name: string;
  middle_name: string;
  last_name: string;
  display_name: string;
  work_email: string;
  personal_email: string;
  employment_status: string;
  joined_on: string;
  access_status: string;
  invited_at: string;
  invite_accepted_at: string;
  access_disabled_at: string;
};

type Props = {
  user: User;
  template: Template;
  employee: Employee | null;
  access: { can_manage_users: boolean };
};

const blankEmployee = {
  employee_number: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  display_name: '',
  work_email: '',
  personal_email: '',
  employment_status: 'active',
  joined_on: '',
};

function displayName(employee: Employee) {
  return employee.display_name || `${employee.first_name} ${employee.last_name}`.trim() || 'Unnamed employee';
}

function FieldLabel({ children, label }: { children: ReactNode; label: string }) {
  return (
    <label className="grid gap-1.5">
      <span className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">{label}</span>
      {children}
    </label>
  );
}

function AccessActions({ canManageUsers, employee }: { canManageUsers: boolean; employee: Employee }) {
  const linkForm = useForm({ email: employee.work_email });
  const hasUser = employee.user_id !== null;

  function postAction(path: string) {
    router.post(path, {}, { preserveScroll: true });
  }

  return (
    <section className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4 shadow-[0_18px_45px_rgba(23,32,27,0.06)]">
      <div className="mb-3">
        <p className="text-sm font-black text-[#17201b]">Login access</p>
        <p className="text-xs text-[#59635d]">
          Current status: {employee.access_status || 'not_invited'}
          {hasUser ? `, linked to user #${employee.user_id}` : ''}
        </p>
      </div>

      <div className="flex flex-wrap gap-2">
        <button className="action-button" type="button" disabled={!canManageUsers || !employee.work_email} onClick={() => postAction(`/employees/${employee.id}/invite`)}>
          <Send className="size-4" />
          Invite
        </button>
        <button className="action-button" type="button" disabled={!canManageUsers || !employee.work_email} onClick={() => postAction(`/employees/${employee.id}/reset-invite`)}>
          <RotateCcw className="size-4" />
          Reset Invite
        </button>
        <button className="action-button" type="button" disabled={!hasUser} onClick={() => postAction(`/employees/${employee.id}/disable-access`)}>
          <ShieldOff className="size-4" />
          Disable
        </button>
        <button className="action-button" type="button" disabled={!hasUser} onClick={() => postAction(`/employees/${employee.id}/unlink-access`)}>
          <Unlink className="size-4" />
          Unlink
        </button>
      </div>

      <form
        className="mt-3 flex flex-col gap-2 sm:flex-row"
        onSubmit={(event) => {
          event.preventDefault();
          linkForm.post(`/employees/${employee.id}/link-user`, { preserveScroll: true });
        }}
      >
        <FieldLabel label="Existing user email">
          <input
            className="field"
            type="email"
            placeholder="asha.rao@example.com"
            value={linkForm.data.email}
            onChange={(event) => linkForm.setData('email', event.target.value)}
          />
        </FieldLabel>
        <button className="action-button justify-center" type="submit" disabled={!canManageUsers || linkForm.processing}>
          <Link2 className="size-4" />
          Link User
        </button>
      </form>

      {!canManageUsers && (
        <p className="mt-3 text-xs font-semibold text-[#a33b2f]">
          Login access is not available for this installation.
        </p>
      )}
    </section>
  );
}

export default function EmployeeFormPage({ user, template, employee, access }: Props) {
  const form = useForm(employee ?? blankEmployee);
  const isEditing = employee !== null;

  return (
    <>
      <Head title={template.title} />
      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="mx-auto grid min-w-0 max-w-5xl gap-4">
          <div className="flex items-center justify-between gap-3">
            <Link className="action-button no-underline" href="/employees">
              <ArrowLeft className="size-4" />
              Back
            </Link>
            {employee && (
              <p className="truncate text-xs font-bold text-[#59635d]">
                Editing {displayName(employee)}
              </p>
            )}
          </div>

          <section className="min-w-0 rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4 shadow-[0_18px_45px_rgba(23,32,27,0.06)]">
            <form
              className="grid gap-4"
              onSubmit={(event) => {
                event.preventDefault();

                if (employee) {
                  form.transform((data) => ({ ...data, _method: 'PUT' }));
                  form.post(`/employees/${employee.id}`);
                  return;
                }

                form.post('/employees');
              }}
            >
              <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <FieldLabel label="Employee number">
                  <input className="field" placeholder="EMP-1042" value={form.data.employee_number} onChange={(event) => form.setData('employee_number', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="First name">
                  <input className="field" placeholder="Asha" value={form.data.first_name} onChange={(event) => form.setData('first_name', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Last name">
                  <input className="field" placeholder="Rao" value={form.data.last_name} onChange={(event) => form.setData('last_name', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Middle name">
                  <input className="field" placeholder="Kumar" value={form.data.middle_name} onChange={(event) => form.setData('middle_name', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Display name">
                  <input className="field" placeholder="Asha Rao" value={form.data.display_name} onChange={(event) => form.setData('display_name', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Joined on">
                  <input className="field" type="date" value={form.data.joined_on} onChange={(event) => form.setData('joined_on', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Work email">
                  <input className="field" type="email" placeholder="asha.rao@company.com" value={form.data.work_email} onChange={(event) => form.setData('work_email', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Personal email">
                  <input className="field" type="email" placeholder="asha.personal@example.com" value={form.data.personal_email} onChange={(event) => form.setData('personal_email', event.target.value)} />
                </FieldLabel>
                <FieldLabel label="Employment status">
                  <select className="field" value={form.data.employment_status} onChange={(event) => form.setData('employment_status', event.target.value)}>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="on_leave">On leave</option>
                  </select>
                </FieldLabel>
              </div>

              <div className="flex justify-end">
                <button
                  className="inline-flex h-8 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-70"
                  type="submit"
                  disabled={form.processing}
                >
                  {isEditing ? <Save className="size-4" /> : <Plus className="size-4" />}
                  {isEditing ? 'Save Employee' : 'Create Employee'}
                </button>
              </div>
            </form>
          </section>

          {employee && <AccessActions canManageUsers={access.can_manage_users} employee={employee} />}
        </div>
      </AppLayout>
    </>
  );
}
