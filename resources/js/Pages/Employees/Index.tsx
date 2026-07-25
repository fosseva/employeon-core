import { Head, Link, router } from '@inertiajs/react';
import {
  ArrowDownAZ,
  ArrowUpAZ,
  BriefcaseBusiness,
  Clock3,
  Eye,
  Filter,
  Mail,
  Plus,
  Search,
  Settings2,
  ShieldCheck,
  Star,
  Trash2,
  UsersRound,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
  employees: Employee[];
  views: EmployeeView[];
};

type ColumnKey =
  | 'employee'
  | 'employee_number'
  | 'work_email'
  | 'personal_email'
  | 'employment_status'
  | 'access_status'
  | 'joined_on'
  | 'user_id';

type EmployeeView = {
  id: string;
  name: string;
  icon: ViewIconKey;
  search: string;
  employmentStatus: string;
  accessStatus: string;
  sortColumn: ColumnKey;
  sortDirection: 'asc' | 'desc';
  columns: ColumnKey[];
  isDefault: boolean;
};

type ViewIconKey = 'eye' | 'users' | 'shield' | 'mail' | 'briefcase' | 'clock' | 'star';

const availableColumns: Array<{ key: ColumnKey; label: string }> = [
  { key: 'employee', label: 'Employee' },
  { key: 'employee_number', label: 'Employee No.' },
  { key: 'work_email', label: 'Work Email' },
  { key: 'personal_email', label: 'Personal Email' },
  { key: 'employment_status', label: 'Employment' },
  { key: 'access_status', label: 'Access' },
  { key: 'joined_on', label: 'Joined On' },
  { key: 'user_id', label: 'Linked User' },
];

const viewIcons = {
  eye: Eye,
  users: UsersRound,
  shield: ShieldCheck,
  mail: Mail,
  briefcase: BriefcaseBusiness,
  clock: Clock3,
  star: Star,
} satisfies Record<ViewIconKey, typeof Eye>;

const availableViewIcons: Array<{ key: ViewIconKey; label: string }> = [
  { key: 'eye', label: 'Default' },
  { key: 'users', label: 'People' },
  { key: 'shield', label: 'Access' },
  { key: 'mail', label: 'Email' },
  { key: 'briefcase', label: 'Work' },
  { key: 'clock', label: 'Pending' },
  { key: 'star', label: 'Priority' },
];

const fallbackView: EmployeeView = {
  id: 'all',
  name: 'All employees',
  icon: 'users',
  search: '',
  employmentStatus: 'all',
  accessStatus: 'all',
  sortColumn: 'employee',
  sortDirection: 'asc',
  columns: ['employee', 'work_email', 'employment_status', 'access_status'],
  isDefault: true,
};

function displayName(employee: Employee) {
  return employee.display_name || `${employee.first_name} ${employee.last_name}`.trim() || 'Unnamed employee';
}

function initials(employee: Employee) {
  return displayName(employee)
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function statusClass(status: string) {
  if (status === 'active') {
    return 'border-[#27615a]/20 bg-[#eef7f3] text-[#27615a]';
  }

  if (status === 'invited') {
    return 'border-[#9a6a2f]/20 bg-[#fff8e9] text-[#8a5a1f]';
  }

  return 'border-[#17201b]/10 bg-[#f7faf4] text-[#59635d]';
}

function formatValue(value: string | number | null) {
  return value === null || value === '' ? '-' : value;
}

function sortValue(employee: Employee, column: ColumnKey) {
  if (column === 'employee') {
    return displayName(employee).toLowerCase();
  }

  const value = column === 'user_id' ? employee.user_id : employee[column];

  return String(value ?? '').toLowerCase();
}

function columnValue(employee: Employee, column: ColumnKey) {
  if (column === 'employee') {
    return (
      <Link className="flex min-w-0 items-center gap-3 text-left no-underline" href={`/employees/${employee.id}/edit`}>
        <span className="grid size-8 shrink-0 place-items-center rounded-md bg-[#27615a] text-xs font-black text-white">{initials(employee)}</span>
        <span className="min-w-0">
          <span className="block truncate font-black text-[#17201b]">{displayName(employee)}</span>
          <span className="block truncate text-xs font-semibold text-[#59635d]">{employee.employee_number || 'No employee number'}</span>
        </span>
      </Link>
    );
  }

  if (column === 'employment_status' || column === 'access_status') {
    const value = employee[column] || (column === 'access_status' ? 'not_invited' : '');

    return (
      <span className={`rounded border px-2 py-1 text-[0.68rem] font-black uppercase ${statusClass(value)}`}>
        {value}
      </span>
    );
  }

  if (column === 'user_id') {
    return employee.user_id ? `User #${employee.user_id}` : '-';
  }

  return formatValue(employee[column]);
}

function ConfirmDialog({
  employee,
  onCancel,
}: {
  employee: Employee | null;
  onCancel: () => void;
}) {
  if (!employee) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-[#17201b]/30 px-4">
      <div className="w-full max-w-md rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5 shadow-[0_24px_70px_rgba(23,32,27,0.22)]">
        <p className="text-sm font-black text-[#17201b]">Delete employee?</p>
        <p className="mt-2 text-sm leading-6 text-[#59635d]">
          {displayName(employee)} and related attendance entries will be removed.
        </p>
        <div className="mt-5 flex justify-end gap-2">
          <button className="action-button" type="button" onClick={onCancel}>
            Cancel
          </button>
          <button
            className="inline-flex h-8 items-center gap-2 rounded-md bg-[#a33b2f] px-3 text-xs font-black text-white"
            type="button"
            onClick={() => {
              router.post(`/employees/${employee.id}`, { _method: 'DELETE' }, { preserveScroll: true });
              onCancel();
            }}
          >
            <Trash2 className="size-4" />
            Delete
          </button>
        </div>
      </div>
    </div>
  );
}

export default function EmployeesIndex({ user, template, employees, views: employeeViews }: Props) {
  const [views, setViews] = useState<EmployeeView[]>(employeeViews.length > 0 ? employeeViews : [fallbackView]);
  const [activeViewId, setActiveViewId] = useState((employeeViews[0] ?? fallbackView).id);
  const [viewName, setViewName] = useState('');
  const [showViewBuilder, setShowViewBuilder] = useState(false);
  const [employeeToDelete, setEmployeeToDelete] = useState<Employee | null>(null);
  const activeView = views.find((view) => view.id === activeViewId) ?? views[0] ?? fallbackView;

  useEffect(() => {
    const nextViews = employeeViews.length > 0 ? employeeViews : [fallbackView];

    setViews(nextViews);

    if (! nextViews.some((view) => view.id === activeViewId)) {
      setActiveViewId(nextViews[0]?.id ?? fallbackView.id);
    }
  }, [activeViewId, employeeViews]);

  function updateActiveView(updates: Partial<EmployeeView>) {
    setViews((currentViews) =>
      currentViews.map((view) => (view.id === activeView.id ? { ...view, ...updates } : view)),
    );
  }

  function toggleColumn(column: ColumnKey) {
    const columns = activeView.columns.includes(column)
      ? activeView.columns.filter((value) => value !== column)
      : [...activeView.columns, column];

    updateActiveView({ columns: columns.length > 0 ? columns : ['employee'] });
  }

  function createView() {
    const name = viewName.trim();

    if (name === '') {
      return;
    }

    const view = {
      ...activeView,
      name,
    };

    router.post('/employees/views', view, {
      preserveScroll: true,
      onSuccess: () => setViewName(''),
    });
  }

  function saveActiveView() {
    router.post(
      `/employees/views/${activeView.id}`,
      { ...activeView, _method: 'PUT' },
      { preserveScroll: true },
    );
  }

  function sortBy(column: ColumnKey) {
    updateActiveView({
      sortColumn: column,
      sortDirection:
        activeView.sortColumn === column && activeView.sortDirection === 'asc' ? 'desc' : 'asc',
    });
  }

  function deleteActiveView() {
    if (activeView.isDefault) {
      return;
    }

    router.post(
      `/employees/views/${activeView.id}`,
      { _method: 'DELETE' },
      {
        preserveScroll: true,
        onSuccess: () => setActiveViewId(fallbackView.id),
      },
    );
  }

  const filteredEmployees = useMemo(() => {
    const term = activeView.search.trim().toLowerCase();

    return employees.filter((employee) =>
      (term === '' || [
        displayName(employee),
        employee.employee_number,
        employee.work_email,
        employee.personal_email,
        employee.employment_status,
        employee.access_status,
      ].join(' ').toLowerCase().includes(term)) &&
      (activeView.employmentStatus === 'all' || employee.employment_status === activeView.employmentStatus) &&
      (activeView.accessStatus === 'all' || (employee.access_status || 'not_invited') === activeView.accessStatus),
    ).sort((left, right) => {
      const direction = activeView.sortDirection === 'asc' ? 1 : -1;

      return sortValue(left, activeView.sortColumn).localeCompare(sortValue(right, activeView.sortColumn)) * direction;
    });
  }, [activeView, employees]);

  const visibleColumns = availableColumns.filter((column) => activeView.columns.includes(column.key));
  const ActiveViewIcon = viewIcons[activeView.icon] ?? Eye;

  return (
    <>
      <Head title={template.title} />
      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="grid gap-4">
          <section className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-3 shadow-[0_18px_45px_rgba(23,32,27,0.06)]">
            <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
              <div className="flex min-w-0 flex-wrap gap-2">
                {views.map((view) => (
                  (() => {
                    const ViewIcon = viewIcons[view.icon] ?? Eye;

                    return (
                  <button
                    className={view.id === activeView.id ? 'inline-flex h-8 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white' : 'inline-flex h-8 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 text-xs font-black text-[#34423a] hover:bg-[#e8efe8]'}
                    key={view.id}
                    type="button"
                    onClick={() => setActiveViewId(view.id)}
                  >
                    <ViewIcon className="size-4" />
                    {view.name}
                  </button>
                    );
                  })()
                ))}
              </div>

              <div className="flex flex-wrap gap-2">
                <label className="flex h-8 min-w-[220px] items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5">
                  <Search className="size-4 text-[#59635d]" />
                  <input
                    className="min-w-0 flex-1 bg-transparent text-xs font-semibold outline-none"
                    placeholder="Search current view"
                    value={activeView.search}
                    onChange={(event) => updateActiveView({ search: event.target.value })}
                  />
                </label>
                <button className="action-button" type="button" onClick={() => setShowViewBuilder((value) => !value)}>
                  <Settings2 className="size-4" />
                  View
                </button>
                <Link className="inline-flex h-8 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white no-underline" href="/employees/create">
                  <Plus className="size-4" />
                  Add Employee
                </Link>
              </div>
            </div>

            {showViewBuilder && (
              <div className="mt-3 grid gap-3 border-t border-[#17201b]/10 pt-3 xl:grid-cols-[1fr_1.3fr_1fr]">
                <div className="grid gap-2">
                  <p className="text-xs font-black uppercase text-[#7a5a3a]">Filters</p>
                  <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                    <select className="field" value={activeView.employmentStatus} onChange={(event) => updateActiveView({ employmentStatus: event.target.value })}>
                      <option value="all">All employment</option>
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                      <option value="on_leave">On leave</option>
                    </select>
                    <select className="field" value={activeView.accessStatus} onChange={(event) => updateActiveView({ accessStatus: event.target.value })}>
                      <option value="all">All access</option>
                      <option value="active">Active</option>
                      <option value="invited">Invited</option>
                      <option value="not_invited">Not invited</option>
                      <option value="disabled">Disabled</option>
                    </select>
                  </div>
                </div>

                <div className="grid gap-2">
                  <p className="text-xs font-black uppercase text-[#7a5a3a]">Columns</p>
                  <div className="flex flex-wrap gap-2">
                    {availableColumns.map((column) => (
                      <label className="flex h-8 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5 text-xs font-bold text-[#34423a]" key={column.key}>
                        <input
                          className="size-3.5 accent-[#27615a]"
                          type="checkbox"
                          checked={activeView.columns.includes(column.key)}
                          onChange={() => toggleColumn(column.key)}
                        />
                        {column.label}
                      </label>
                    ))}
                  </div>
                </div>

                <div className="grid content-start gap-2">
                  <p className="text-xs font-black uppercase text-[#7a5a3a]">View details</p>
                  <select className="field" value={activeView.icon} onChange={(event) => updateActiveView({ icon: event.target.value as ViewIconKey })}>
                    {availableViewIcons.map((icon) => (
                      <option key={icon.key} value={icon.key}>{icon.label} icon</option>
                    ))}
                  </select>
                  <div className="grid gap-2 sm:grid-cols-2">
                    <select className="field" value={activeView.sortColumn} onChange={(event) => updateActiveView({ sortColumn: event.target.value as ColumnKey })}>
                      {availableColumns.map((column) => (
                        <option key={column.key} value={column.key}>{column.label}</option>
                      ))}
                    </select>
                    <select className="field" value={activeView.sortDirection} onChange={(event) => updateActiveView({ sortDirection: event.target.value as 'asc' | 'desc' })}>
                      <option value="asc">Ascending</option>
                      <option value="desc">Descending</option>
                    </select>
                  </div>

                  <p className="text-xs font-black uppercase text-[#7a5a3a]">Create view</p>
                  <div className="flex gap-2">
                    <input className="field" placeholder="View name" value={viewName} onChange={(event) => setViewName(event.target.value)} />
                    <button className="action-button" type="button" onClick={createView}>
                      <Plus className="size-4" />
                      Create
                    </button>
                  </div>
                  <button className="action-button justify-center" type="button" onClick={saveActiveView}>
                    Save current view
                  </button>
                  {!activeView.isDefault && (
                    <button className="inline-flex h-8 items-center justify-center gap-2 rounded-md border border-[#a33b2f]/20 px-3 text-xs font-black text-[#a33b2f] hover:bg-[#fff4f1]" type="button" onClick={deleteActiveView}>
                      <Trash2 className="size-4" />
                      Delete current view
                    </button>
                  )}
                </div>
              </div>
            )}
          </section>

          <section className="overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] shadow-[0_18px_45px_rgba(23,32,27,0.07)]">
            <div className="flex flex-col gap-3 border-b border-[#17201b]/10 px-4 py-3 md:flex-row md:items-center md:justify-between">
              <div>
                <p className="flex items-center gap-2 text-sm font-black text-[#17201b]">
                  <ActiveViewIcon className="size-4 text-[#27615a]" />
                  {activeView.name}
                </p>
                <p className="text-xs text-[#59635d]">
                  {filteredEmployees.length} of {employees.length} people shown
                </p>
              </div>
              <div className="inline-flex items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5 py-1.5 text-xs font-bold text-[#59635d]">
                <Filter className="size-4 text-[#27615a]" />
                {visibleColumns.length} columns
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full min-w-[760px] text-left text-sm">
                <thead className="bg-[#f7faf4] text-[0.68rem] font-black uppercase text-[#7a5a3a]">
                  <tr>
                    {visibleColumns.map((column) => (
                      <th className="px-4 py-2.5" key={column.key}>
                        <button className="inline-flex items-center gap-1.5 text-left" type="button" onClick={() => sortBy(column.key)}>
                          {column.label}
                          {activeView.sortColumn === column.key && (
                            activeView.sortDirection === 'asc' ? <ArrowDownAZ className="size-3.5" /> : <ArrowUpAZ className="size-3.5" />
                          )}
                        </button>
                      </th>
                    ))}
                    <th className="px-4 py-2.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#17201b]/10">
                  {filteredEmployees.map((employee) => (
                    <tr className="hover:bg-[#f7faf4]" key={employee.id}>
                      {visibleColumns.map((column) => (
                        <td className="px-4 py-3 text-xs font-semibold text-[#59635d]" key={column.key}>
                          {columnValue(employee, column.key)}
                        </td>
                      ))}
                      <td className="px-4 py-3">
                        <div className="flex justify-end gap-2">
                          <Link className="action-button no-underline" href={`/employees/${employee.id}/edit`}>Edit</Link>
                          <button className="grid size-8 place-items-center rounded-md border border-[#a33b2f]/20 text-[#a33b2f] hover:bg-[#fff4f1]" type="button" onClick={() => setEmployeeToDelete(employee)} title="Delete employee">
                            <Trash2 className="size-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {filteredEmployees.length === 0 && (
              <p className="px-4 py-10 text-center text-sm font-semibold text-[#59635d]">No employees match this view.</p>
            )}
          </section>
        </div>
      </AppLayout>

      <ConfirmDialog employee={employeeToDelete} onCancel={() => setEmployeeToDelete(null)} />
    </>
  );
}
