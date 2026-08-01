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
  filterQuery: string;
  sortColumn: ColumnKey;
  sortDirection: 'asc' | 'desc';
  columns: ColumnKey[];
  isDefault: boolean;
};

type EmployeeFilters = {
  text: string;
  rules: Record<string, string>;
};

type ViewIconKey = 'eye' | 'users' | 'shield' | 'mail' | 'briefcase' | 'clock' | 'star';

type EmployeeField = {
  key: ColumnKey;
  label: string;
  group: string;
  description: string;
  column: boolean;
  sortable: boolean;
  filter?: {
    key: string;
    token: string;
    label: string;
    options: Array<{ value: string; label: string }>;
    matches: (employee: Employee, value: string) => boolean;
  };
};

const employeeFields: EmployeeField[] = [
  {
    key: 'employee',
    label: 'Employee',
    group: 'Profile',
    description: 'Name, avatar initials, and employee number.',
    column: true,
    sortable: true,
  },
  {
    key: 'employee_number',
    label: 'Employee No.',
    group: 'Profile',
    description: 'Internal employee identifier.',
    column: true,
    sortable: true,
  },
  {
    key: 'work_email',
    label: 'Work Email',
    group: 'Contact',
    description: 'Official work email address.',
    column: true,
    sortable: true,
  },
  {
    key: 'personal_email',
    label: 'Personal Email',
    group: 'Contact',
    description: 'Personal contact email address.',
    column: true,
    sortable: true,
  },
  {
    key: 'employment_status',
    label: 'Employment',
    group: 'Employment',
    description: 'Current employment lifecycle state.',
    column: true,
    sortable: true,
    filter: {
      key: 'employment',
      token: 'employment',
      label: 'Employment',
      options: [
        { value: 'all', label: 'All employment' },
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
        { value: 'on_leave', label: 'On leave' },
      ],
      matches: (employee, value) => value === 'all' || employee.employment_status === value,
    },
  },
  {
    key: 'access_status',
    label: 'Access',
    group: 'Access',
    description: 'Login invitation and account access state.',
    column: true,
    sortable: true,
    filter: {
      key: 'access',
      token: 'access',
      label: 'Access',
      options: [
        { value: 'all', label: 'All access' },
        { value: 'active', label: 'Active' },
        { value: 'invited', label: 'Invited' },
        { value: 'not_invited', label: 'Not invited' },
        { value: 'disabled', label: 'Disabled' },
      ],
      matches: (employee, value) => value === 'all' || (employee.access_status || 'not_invited') === value,
    },
  },
  {
    key: 'joined_on',
    label: 'Joined On',
    group: 'Employment',
    description: 'Date when the employee joined.',
    column: true,
    sortable: true,
  },
  {
    key: 'user_id',
    label: 'Linked User',
    group: 'Access',
    description: 'Whether this employee is linked to a login user.',
    column: true,
    sortable: true,
  },
];

const availableColumns = employeeFields.filter((field) => field.column);
const sortableFields = employeeFields.filter((field) => field.sortable);
const availableFilters = employeeFields.flatMap((field) => field.filter ? [field.filter] : []);

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
  filterQuery: '',
  sortColumn: 'employee',
  sortDirection: 'asc',
  columns: ['employee', 'work_email', 'employment_status', 'access_status'],
  isDefault: true,
};

const emptyFilters: EmployeeFilters = {
  text: '',
  rules: {},
};

function normalizeFilterQuery(query: string) {
  return query.trim().replace(/\s+/g, ' ');
}

function parseFilterQuery(query: string): EmployeeFilters {
  const filters: EmployeeFilters = { text: '', rules: {} };
  const textParts: string[] = [];

  for (const token of normalizeFilterQuery(query).split(' ').filter(Boolean)) {
    const filter = availableFilters.find((availableFilter) => token.startsWith(`${availableFilter.token}:`));

    if (filter) {
      filters.rules[filter.key] = token.slice(`${filter.token}:`.length) || 'all';

      continue;
    }

    textParts.push(token);
  }

  filters.text = textParts.join(' ');

  return filters;
}

function buildFilterQuery(filters: EmployeeFilters) {
  return [
    filters.text.trim(),
    ...availableFilters.map((filter) => {
      const value = filters.rules[filter.key] ?? 'all';

      return value !== 'all' ? `${filter.token}:${value}` : '';
    }),
  ].filter(Boolean).join(' ');
}

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
  const [filterQuery, setFilterQuery] = useState((employeeViews[0] ?? fallbackView).filterQuery);
  const [columnSearch, setColumnSearch] = useState('');
  const [employeeToDelete, setEmployeeToDelete] = useState<Employee | null>(null);
  const activeView = views.find((view) => view.id === activeViewId) ?? views[0] ?? fallbackView;

  useEffect(() => {
    const nextViews = employeeViews.length > 0 ? employeeViews : [fallbackView];

    setViews(nextViews);

    if (! nextViews.some((view) => view.id === activeViewId)) {
      setActiveViewId(nextViews[0]?.id ?? fallbackView.id);
    }
  }, [activeViewId, employeeViews]);

  useEffect(() => {
    setFilterQuery(activeView.filterQuery);
  }, [activeView.id, activeView.filterQuery]);

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

    const view = viewPayload({
      ...activeView,
      name,
    });

    router.post('/employees/views', view, {
      preserveScroll: true,
      onSuccess: () => setViewName(''),
    });
  }

  function saveActiveView() {
    router.post(
      `/employees/views/${activeView.id}`,
      { ...viewPayload(activeView), _method: 'PUT' },
      { preserveScroll: true },
    );
  }

  function viewPayload(view: EmployeeView) {
    return {
      name: view.name,
      icon: view.icon,
      filterQuery: normalizeFilterQuery(filterQuery),
      sortColumn: view.sortColumn,
      sortDirection: view.sortDirection,
      columns: view.columns,
    };
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

  function updateFilterQuery(updates: Partial<EmployeeFilters>) {
    const currentFilters = parseFilterQuery(filterQuery);

    setFilterQuery(buildFilterQuery({
      ...currentFilters,
      ...updates,
      rules: {
        ...currentFilters.rules,
        ...updates.rules,
      },
    }));
  }

  const filteredEmployees = useMemo(() => {
    const parsedFilters = parseFilterQuery(filterQuery);
    const term = parsedFilters.text.trim().toLowerCase();

    return employees.filter((employee) =>
      (term === '' || [
        displayName(employee),
        employee.employee_number,
        employee.work_email,
        employee.personal_email,
        employee.employment_status,
        employee.access_status,
      ].join(' ').toLowerCase().includes(term)) &&
      availableFilters.every((filter) => filter.matches(employee, parsedFilters.rules[filter.key] ?? 'all')),
    ).sort((left, right) => {
      const direction = activeView.sortDirection === 'asc' ? 1 : -1;

      return sortValue(left, activeView.sortColumn).localeCompare(sortValue(right, activeView.sortColumn)) * direction;
    });
  }, [activeView.sortColumn, activeView.sortDirection, employees, filterQuery]);

  const visibleColumns = availableColumns.filter((column) => activeView.columns.includes(column.key));
  const mobileColumns = visibleColumns.filter((column) => column.key !== 'employee');
  const ActiveViewIcon = viewIcons[activeView.icon] ?? Eye;
  const filters = parseFilterQuery(filterQuery);
  const activeFilterCount = [
    filters.text.trim() !== '',
    ...availableFilters.map((filter) => (filters.rules[filter.key] ?? 'all') !== 'all'),
  ].filter(Boolean).length;
  const hasUnsavedFilterQuery = normalizeFilterQuery(filterQuery) !== normalizeFilterQuery(activeView.filterQuery);
  const filteredColumnFields = availableColumns.filter((field) =>
    [field.label, field.group, field.description, field.key].join(' ').toLowerCase().includes(columnSearch.trim().toLowerCase()),
  );
  const columnGroups = Array.from(new Set(filteredColumnFields.map((field) => field.group)));

  return (
    <>
      <Head title={template.title} />
      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="grid gap-4">
          <section className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-3 shadow-[0_18px_45px_rgba(23,32,27,0.06)]">
            <div className="grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
              <div className="flex min-w-0 flex-wrap gap-2">
                {views.map((view) => (
                  (() => {
                    const ViewIcon = viewIcons[view.icon] ?? Eye;

                    return (
                  <button
                    className={view.id === activeView.id ? 'inline-flex h-8 min-w-0 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white' : 'inline-flex h-8 min-w-0 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 text-xs font-black text-[#34423a] hover:bg-[#e8efe8]'}
                    key={view.id}
                    type="button"
                    onClick={() => setActiveViewId(view.id)}
                  >
                    <ViewIcon className="size-4 shrink-0" />
                    <span className="truncate">{view.name}</span>
                  </button>
                    );
                  })()
                ))}
              </div>

              <div className="flex shrink-0 flex-wrap gap-2 xl:justify-end">
                <button className="action-button shrink-0" type="button" onClick={() => setShowViewBuilder((value) => !value)}>
                  <Settings2 className="size-4" />
                  View
                </button>
                <Link className="inline-flex h-8 shrink-0 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white no-underline" href="/employees/create">
                  <Plus className="size-4" />
                  <span className="sm:hidden">Add</span>
                  <span className="hidden sm:inline">Add Employee</span>
                </Link>
              </div>
            </div>

            <div className="mt-3 flex flex-col gap-2 border-t border-[#17201b]/10 pt-3">
              <label className="flex h-9 min-w-0 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5">
                <Search className="size-4 shrink-0 text-[#59635d]" />
                <input
                  className="min-w-0 flex-1 bg-transparent text-xs font-semibold outline-none"
                  placeholder="Filter employees, e.g. aniket employment:active access:invited"
                  value={filterQuery}
                  onChange={(event) => setFilterQuery(event.target.value)}
                />
                {activeFilterCount > 0 && (
                  <span className="rounded bg-[#17201b]/10 px-1.5 py-0.5 text-[0.62rem] font-black text-[#34423a]">
                    {activeFilterCount}
                  </span>
                )}
              </label>
              <div className="flex flex-wrap gap-2">
                {availableFilters.map((filter) => (
                  <select
                    className="field h-9 w-full sm:w-auto sm:min-w-40"
                    key={filter.key}
                    value={filters.rules[filter.key] ?? 'all'}
                    onChange={(event) => updateFilterQuery({ rules: { [filter.key]: event.target.value } })}
                  >
                    {filter.options.map((option) => (
                      <option key={option.value} value={option.value}>{option.label}</option>
                    ))}
                  </select>
                ))}
                <button className="action-button h-9 justify-center" type="button" onClick={() => setFilterQuery(activeView.filterQuery)} disabled={!hasUnsavedFilterQuery}>
                  Discard
                </button>
                <button className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-60" type="button" onClick={saveActiveView} disabled={!hasUnsavedFilterQuery}>
                  Save
                </button>
              </div>
            </div>

            {showViewBuilder && (
              <div className="mt-3 rounded-md border border-[#17201b]/10 bg-[#f7faf4] p-3">
                <div className="flex flex-col gap-1 border-b border-[#17201b]/10 pb-3 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <p className="text-sm font-black text-[#17201b]">Configure view</p>
                    <p className="text-xs text-[#59635d]">Columns, icon, sort, and the current filter query are saved to this view.</p>
                  </div>
                  <button className="action-button justify-center" type="button" onClick={saveActiveView}>
                    Save current view
                  </button>
                </div>

                <div className="mt-3 grid gap-3 xl:grid-cols-[360px_minmax(0,1fr)]">
                  <div className="grid gap-3">
                    <div className="grid gap-2 rounded-md border border-[#17201b]/10 bg-[#fffffb] p-3">
                      <p className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">Sort and icon</p>
                      <select className="field" value={activeView.icon} onChange={(event) => updateActiveView({ icon: event.target.value as ViewIconKey })}>
                        {availableViewIcons.map((icon) => (
                          <option key={icon.key} value={icon.key}>{icon.label} icon</option>
                        ))}
                      </select>
                      <div className="grid gap-2 sm:grid-cols-2">
                        <select className="field" value={activeView.sortColumn} onChange={(event) => updateActiveView({ sortColumn: event.target.value as ColumnKey })}>
                          {sortableFields.map((column) => (
                            <option key={column.key} value={column.key}>{column.label}</option>
                          ))}
                        </select>
                        <select className="field" value={activeView.sortDirection} onChange={(event) => updateActiveView({ sortDirection: event.target.value as 'asc' | 'desc' })}>
                          <option value="asc">Ascending</option>
                          <option value="desc">Descending</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div className="grid gap-3 rounded-md border border-[#17201b]/10 bg-[#fffffb] p-3">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <p className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">Columns</p>
                        <p className="text-xs text-[#59635d]">Pick fields from the directory schema.</p>
                      </div>
                      <p className="text-xs font-semibold text-[#59635d]">{visibleColumns.length} selected</p>
                    </div>

                    <label className="flex h-9 min-w-0 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5">
                      <Search className="size-4 shrink-0 text-[#59635d]" />
                      <input
                        className="min-w-0 flex-1 bg-transparent text-xs font-semibold outline-none"
                        placeholder="Find columns"
                        value={columnSearch}
                        onChange={(event) => setColumnSearch(event.target.value)}
                      />
                    </label>

                    <div className="flex flex-wrap gap-2">
                      {visibleColumns.map((column) => (
                        <span className="inline-flex h-7 items-center rounded-md bg-[#27615a] px-2.5 text-[0.68rem] font-black text-white" key={column.key}>
                          {column.label}
                        </span>
                      ))}
                    </div>

                    <div className="grid max-h-[340px] gap-3 overflow-y-auto pr-1">
                      {columnGroups.map((group) => (
                        <div className="grid gap-2" key={group}>
                          <p className="text-[0.65rem] font-black uppercase text-[#7a5a3a]">{group}</p>
                          <div className="grid gap-2 md:grid-cols-2 2xl:grid-cols-3">
                            {filteredColumnFields.filter((field) => field.group === group).map((field) => (
                              <label
                                className={activeView.columns.includes(field.key) ? 'grid cursor-pointer gap-1 rounded-md border border-[#27615a]/25 bg-[#eef7f3] p-2.5 text-left' : 'grid cursor-pointer gap-1 rounded-md border border-[#17201b]/10 bg-[#f7faf4] p-2.5 text-left hover:bg-[#eef3ec]'}
                                key={field.key}
                              >
                                <span className="flex items-start gap-2">
                                  <input
                                    className="mt-0.5 size-3.5 shrink-0 accent-[#27615a]"
                                    type="checkbox"
                                    checked={activeView.columns.includes(field.key)}
                                    onChange={() => toggleColumn(field.key)}
                                  />
                                  <span className="min-w-0">
                                    <span className="block truncate text-xs font-black text-[#17201b]">{field.label}</span>
                                    <span className="block text-[0.68rem] leading-4 text-[#59635d]">{field.description}</span>
                                  </span>
                                </span>
                              </label>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                <div className="mt-3 flex flex-col gap-2 border-t border-[#17201b]/10 pt-3 lg:flex-row lg:items-center lg:justify-between">
                  <div className="flex min-w-0 flex-1 gap-2">
                    <input className="field max-w-sm" placeholder="New view name" value={viewName} onChange={(event) => setViewName(event.target.value)} />
                    <button className="action-button" type="button" onClick={createView}>
                      <Plus className="size-4" />
                      Create
                    </button>
                  </div>
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
            <div className="flex flex-col gap-3 border-b border-[#17201b]/10 px-4 py-3 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <p className="flex items-center gap-2 text-sm font-black text-[#17201b]">
                  <ActiveViewIcon className="size-4 text-[#27615a]" />
                  {activeView.name}
                </p>
                <p className="text-xs text-[#59635d]">
                  {filteredEmployees.length} of {employees.length} people shown
                </p>
              </div>
              <div className="flex flex-col gap-2 sm:flex-row sm:items-center xl:justify-end">
                <div className="inline-flex h-8 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5 text-xs font-bold text-[#59635d]">
                  <Filter className="size-4 text-[#27615a]" />
                  {visibleColumns.length} columns
                </div>
              </div>
            </div>

            <div className="divide-y divide-[#17201b]/10 md:hidden">
              {filteredEmployees.map((employee) => {
                const accessStatus = employee.access_status || 'not_invited';

                return (
                  <div className="grid gap-3 px-4 py-3" key={employee.id}>
                    <div className="flex min-w-0 items-start justify-between gap-3">
                      {columnValue(employee, 'employee')}
                      <button className="grid size-8 shrink-0 place-items-center rounded-md border border-[#a33b2f]/20 text-[#a33b2f] hover:bg-[#fff4f1]" type="button" onClick={() => setEmployeeToDelete(employee)} title="Delete employee">
                        <Trash2 className="size-4" />
                      </button>
                    </div>

                    <div className="grid gap-2 text-xs font-semibold text-[#59635d]">
                      {mobileColumns.map((column) => (
                        <div className="grid gap-1" key={column.key}>
                          <span className="text-[0.62rem] font-black uppercase text-[#7a5a3a]">
                            {column.label}
                          </span>
                          <span className="min-w-0 break-words">
                            {column.key === 'access_status' ? (
                              <span className={`w-fit rounded border px-2 py-1 text-[0.68rem] font-black uppercase ${statusClass(accessStatus)}`}>
                                {accessStatus}
                              </span>
                            ) : (
                              columnValue(employee, column.key)
                            )}
                          </span>
                        </div>
                      ))}
                    </div>

                    <Link className="action-button w-fit no-underline" href={`/employees/${employee.id}/edit`}>Edit employee</Link>
                  </div>
                );
              })}
            </div>

            <div className="hidden overflow-x-auto md:block">
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
