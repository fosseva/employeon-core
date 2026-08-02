import { Head, Link, router } from '@inertiajs/react';
import {
  Archive,
  ArrowDownAZ,
  ArrowUpAZ,
  BadgeCheck,
  Bell,
  BriefcaseBusiness,
  Building2,
  CalendarDays,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  ChevronUp,
  Clock3,
  ClipboardCheck,
  Download,
  Eye,
  FileText,
  Flag,
  GraduationCap,
  GripVertical,
  HeartHandshake,
  IdCard,
  Mail,
  MailCheck,
  MapPin,
  MoreHorizontal,
  Pencil,
  Plane,
  Plus,
  Search,
  ShieldCheck,
  SlidersHorizontal,
  Star,
  Trash2,
  Trophy,
  Upload,
  UserCheck,
  UserPlus,
  UserRoundCheck,
  UserRoundX,
  Users,
  UsersRound,
  WalletCards,
  Wrench,
  X,
} from 'lucide-react';
import { type KeyboardEvent, type MouseEvent, useEffect, useMemo, useState } from 'react';
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
  sorts?: SortRule[];
  columns: ColumnKey[];
  isDefault: boolean;
};

type SortRule = {
  column: ColumnKey;
  direction: 'asc' | 'desc';
};

type EmployeeFilters = {
  text: string;
  rules: Record<string, string[]>;
};

type FilterMenuPlacement = {
  key: string;
  left: number;
  top: number;
  width: number;
};

type ViewBuilderTab = 'basics' | 'sort' | 'columns';

type ViewIconKey =
  | 'eye'
  | 'users'
  | 'shield'
  | 'mail'
  | 'briefcase'
  | 'clock'
  | 'star'
  | 'badge'
  | 'file'
  | 'heart'
  | 'mail-check'
  | 'user-check'
  | 'user-linked'
  | 'user-disabled'
  | 'archive'
  | 'bell'
  | 'building'
  | 'calendar'
  | 'clipboard'
  | 'flag'
  | 'graduation'
  | 'id-card'
  | 'location'
  | 'plane'
  | 'trophy'
  | 'user-plus'
  | 'team'
  | 'wallet'
  | 'wrench';

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
    multiple?: boolean;
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
      multiple: true,
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
      multiple: true,
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
  badge: BadgeCheck,
  file: FileText,
  heart: HeartHandshake,
  'mail-check': MailCheck,
  'user-check': UserCheck,
  'user-linked': UserRoundCheck,
  'user-disabled': UserRoundX,
  archive: Archive,
  bell: Bell,
  building: Building2,
  calendar: CalendarDays,
  clipboard: ClipboardCheck,
  flag: Flag,
  graduation: GraduationCap,
  'id-card': IdCard,
  location: MapPin,
  plane: Plane,
  trophy: Trophy,
  'user-plus': UserPlus,
  team: Users,
  wallet: WalletCards,
  wrench: Wrench,
} satisfies Record<ViewIconKey, typeof Eye>;

const availableViewIcons: Array<{ key: ViewIconKey; label: string }> = [
  { key: 'eye', label: 'Default' },
  { key: 'users', label: 'People' },
  { key: 'shield', label: 'Access' },
  { key: 'mail', label: 'Email' },
  { key: 'briefcase', label: 'Work' },
  { key: 'clock', label: 'Pending' },
  { key: 'star', label: 'Priority' },
  { key: 'badge', label: 'Verified' },
  { key: 'file', label: 'Records' },
  { key: 'heart', label: 'Care' },
  { key: 'mail-check', label: 'Confirmed mail' },
  { key: 'user-check', label: 'Ready user' },
  { key: 'user-linked', label: 'Linked user' },
  { key: 'user-disabled', label: 'Disabled user' },
  { key: 'archive', label: 'Archived' },
  { key: 'bell', label: 'Alert' },
  { key: 'building', label: 'Office' },
  { key: 'calendar', label: 'Calendar' },
  { key: 'clipboard', label: 'Checklist' },
  { key: 'flag', label: 'Flag' },
  { key: 'graduation', label: 'Training' },
  { key: 'id-card', label: 'Identity' },
  { key: 'location', label: 'Location' },
  { key: 'plane', label: 'Travel' },
  { key: 'trophy', label: 'Top performers' },
  { key: 'user-plus', label: 'New joiners' },
  { key: 'team', label: 'Team' },
  { key: 'wallet', label: 'Payroll' },
  { key: 'wrench', label: 'Tools' },
];

const defaultVisibleIconCount = 7;

const fallbackView: EmployeeView = {
  id: 'all',
  name: 'All employees',
  icon: 'users',
  filterQuery: '',
  sortColumn: 'employee',
  sortDirection: 'asc',
  sorts: [{ column: 'employee', direction: 'asc' }],
  columns: ['employee', 'work_email', 'employment_status', 'access_status'],
  isDefault: true,
};

const activeViewStorageKey = 'employeon.employees.activeViewId';

const emptyFilters: EmployeeFilters = {
  text: '',
  rules: {},
};

function slugifyViewName(name: string) {
  return name
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    || 'view';
}

function viewUrlToken(view: EmployeeView, views: EmployeeView[]) {
  if (view.isDefault) {
    return view.id;
  }

  const baseToken = slugifyViewName(view.name);
  const matchingViews = views.filter((candidate) => !candidate.isDefault && slugifyViewName(candidate.name) === baseToken);
  const matchingIndex = matchingViews.findIndex((candidate) => candidate.id === view.id);
  const token = matchingIndex > 0 ? `${baseToken}-${matchingIndex + 1}` : baseToken;

  return views.some((candidate) => candidate.id === token && candidate.id !== view.id)
    ? `view-${token}`
    : token;
}

function resolveViewToken(token: string | null, views: EmployeeView[]) {
  if (!token) {
    return null;
  }

  return views.find((view) => viewUrlToken(view, views) === token)?.id ?? null;
}

function resolveInitialActiveViewId(views: EmployeeView[]) {
  if (typeof window === 'undefined') {
    return views[0]?.id ?? fallbackView.id;
  }

  const requestedViewId = resolveViewToken(new URLSearchParams(window.location.search).get('view'), views)
    ?? resolveViewToken(window.localStorage.getItem(activeViewStorageKey), views);

  return requestedViewId && views.some((view) => view.id === requestedViewId)
    ? requestedViewId
    : views[0]?.id ?? fallbackView.id;
}

function normalizeFilterQuery(query: string) {
  return query.trim().replace(/\s+/g, ' ');
}

function parseFilterQuery(query: string): EmployeeFilters {
  const filters: EmployeeFilters = { text: '', rules: {} };
  const textParts: string[] = [];

  for (const token of normalizeFilterQuery(query).split(' ').filter(Boolean)) {
    const filter = availableFilters.find((availableFilter) => token.startsWith(`${availableFilter.token}:`));

    if (filter) {
      const values = token
        .slice(`${filter.token}:`.length)
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== '' && value !== 'all');

      filters.rules[filter.key] = Array.from(new Set([
        ...(filters.rules[filter.key] ?? []),
        ...values,
      ]));

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
    ...availableFilters.flatMap((filter) => (filters.rules[filter.key] ?? [])
      .filter((value) => value !== 'all')
      .map((value) => `${filter.token}:${value}`)),
  ].filter(Boolean).join(' ');
}

function selectedFilterValues(filters: EmployeeFilters, filter: (typeof availableFilters)[number]) {
  return (filters.rules[filter.key] ?? []).filter((value) =>
    value !== 'all' && filter.options.some((option) => option.value === value),
  );
}

function filterSummary(filters: EmployeeFilters, filter: (typeof availableFilters)[number]) {
  const selectedValues = selectedFilterValues(filters, filter);

  if (selectedValues.length === 0) {
    return filter.options.find((option) => option.value === 'all')?.label ?? 'All';
  }

  const firstLabel = filter.options.find((option) => option.value === selectedValues[0])?.label ?? selectedValues[0];

  return selectedValues.length === 1 ? firstLabel : `${firstLabel} +${selectedValues.length - 1}`;
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

function employeeMatchesFilterQuery(employee: Employee, query: string) {
  const parsedFilters = parseFilterQuery(query);
  const term = parsedFilters.text.trim().toLowerCase();
  const textMatches = term === '' || [
    displayName(employee),
    employee.employee_number,
    employee.work_email,
    employee.personal_email,
    employee.employment_status,
    employee.access_status,
  ].join(' ').toLowerCase().includes(term);

  return textMatches && availableFilters.every((filter) => {
    const selectedValues = selectedFilterValues(parsedFilters, filter);

    return selectedValues.length === 0 || selectedValues.some((value) => filter.matches(employee, value));
  });
}

function viewSorts(view: EmployeeView): SortRule[] {
  return view.sorts && view.sorts.length > 0
    ? view.sorts
    : [{ column: view.sortColumn, direction: view.sortDirection }];
}

function normalizeSorts(sorts: SortRule[]): SortRule[] {
  const normalized: SortRule[] = [];

  for (const sort of sorts) {
    if (normalized.some((existingSort) => existingSort.column === sort.column)) {
      continue;
    }

    normalized.push({
      column: sort.column,
      direction: sort.direction === 'desc' ? 'desc' : 'asc',
    });
  }

  return normalized.length > 0 ? normalized.slice(0, 3) : [{ column: 'employee', direction: 'asc' }];
}

function sortSummary(sorts: SortRule[]) {
  return sorts
    .map((sort) => {
      const field = sortableFields.find((candidate) => candidate.key === sort.column);
      const direction = sort.direction === 'asc' ? 'A-Z' : 'Z-A';

      return `${field?.label ?? sort.column} ${direction}`;
    })
    .join(', ');
}

function createUuid() {
  const bytes = globalThis.crypto?.getRandomValues(new Uint8Array(16));

  if (!bytes) {
    throw new Error('Browser crypto is required to create saved views.');
  }

  bytes[6] = (bytes[6] & 0x0f) | 0x40;
  bytes[8] = (bytes[8] & 0x3f) | 0x80;

  const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0'));

  return [
    hex.slice(0, 4).join(''),
    hex.slice(4, 6).join(''),
    hex.slice(6, 8).join(''),
    hex.slice(8, 10).join(''),
    hex.slice(10, 16).join(''),
  ].join('-');
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
  const initialViews = employeeViews.length > 0 ? employeeViews : [fallbackView];
  const initialActiveViewId = resolveInitialActiveViewId(initialViews);
  const initialActiveView = initialViews.find((view) => view.id === initialActiveViewId) ?? initialViews[0] ?? fallbackView;
  const [views, setViews] = useState<EmployeeView[]>(initialViews);
  const [activeViewId, setActiveViewId] = useState(initialActiveViewId);
  const [editingViewId, setEditingViewId] = useState<string | null>(null);
  const [showViewBuilder, setShowViewBuilder] = useState(false);
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
  const [showAllIcons, setShowAllIcons] = useState(false);
  const [viewBuilderTab, setViewBuilderTab] = useState<ViewBuilderTab>('basics');
  const [openFilterMenu, setOpenFilterMenu] = useState<FilterMenuPlacement | null>(null);
  const [canScrollViewsLeft, setCanScrollViewsLeft] = useState(false);
  const [canScrollViewsRight, setCanScrollViewsRight] = useState(false);
  const [filterQuery, setFilterQuery] = useState(initialActiveView.filterQuery);
  const [columnSearch, setColumnSearch] = useState('');
  const [employeeToDelete, setEmployeeToDelete] = useState<Employee | null>(null);
  const [selectedEmployeeIds, setSelectedEmployeeIds] = useState<number[]>([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const activeView = views.find((view) => view.id === activeViewId) ?? views[0] ?? fallbackView;

  function syncViewScrollState() {
    if (typeof window === 'undefined') {
      return;
    }

    const strip = document.querySelector<HTMLElement>('[data-employee-view-strip]');

    if (!strip) {
      return;
    }

    setCanScrollViewsLeft(strip.scrollLeft > 1);
    setCanScrollViewsRight(strip.scrollLeft + strip.clientWidth < strip.scrollWidth - 1);
  }

  useEffect(() => {
    const nextViews = employeeViews.length > 0 ? employeeViews : [fallbackView];
    const requestedViewId = resolveInitialActiveViewId(nextViews);

    setViews(nextViews);

    if (nextViews.some((view) => view.id === activeViewId)) {
      return;
    }

    setActiveViewId(requestedViewId);
  }, [employeeViews]);

  useEffect(() => {
    setFilterQuery(activeView.filterQuery);
  }, [activeView.id, activeView.filterQuery]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    window.localStorage.setItem(activeViewStorageKey, activeView.id);

    const url = new URL(window.location.href);
    url.searchParams.set('view', viewUrlToken(activeView, views));
    window.history.replaceState(window.history.state, '', url);
  }, [activeView, views]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    window.requestAnimationFrame(() => {
      const activeTab = document.querySelector<HTMLElement>(`[data-employee-view-id="${activeView.id}"]`);
      activeTab?.scrollIntoView({ block: 'nearest', inline: 'center' });
      syncViewScrollState();
    });
  }, [activeView.id, views.length]);

  useEffect(() => {
    syncViewScrollState();

    if (typeof window === 'undefined') {
      return;
    }

    window.addEventListener('resize', syncViewScrollState);

    return () => window.removeEventListener('resize', syncViewScrollState);
  }, [views.length]);

  useEffect(() => {
    setCurrentPage(1);
  }, [activeView.id, filterQuery, rowsPerPage]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const closeFilterMenu = () => setOpenFilterMenu(null);
    const closeFilterMenuOnOutsideClick = (event: globalThis.MouseEvent) => {
      const target = event.target instanceof Element ? event.target : null;

      if (target?.closest('[data-employee-filter-menu], [data-employee-filter-button]')) {
        return;
      }

      setOpenFilterMenu(null);
    };

    window.addEventListener('mousedown', closeFilterMenuOnOutsideClick);
    window.addEventListener('resize', closeFilterMenu);
    window.addEventListener('scroll', closeFilterMenu, true);

    return () => {
      window.removeEventListener('mousedown', closeFilterMenuOnOutsideClick);
      window.removeEventListener('resize', closeFilterMenu);
      window.removeEventListener('scroll', closeFilterMenu, true);
    };
  }, []);

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

  function moveColumn(column: ColumnKey, direction: 'up' | 'down') {
    const index = activeView.columns.indexOf(column);

    if (index === -1) {
      return;
    }

    const nextIndex = direction === 'up' ? index - 1 : index + 1;

    if (nextIndex < 0 || nextIndex >= activeView.columns.length) {
      return;
    }

    const columns = [...activeView.columns];
    [columns[index], columns[nextIndex]] = [columns[nextIndex], columns[index]];

    updateActiveView({ columns });
  }

  function reorderColumn(column: ColumnKey, targetColumn: ColumnKey) {
    if (column === targetColumn) {
      return;
    }

    const columns = activeView.columns.filter((value) => value !== column);
    const targetIndex = columns.indexOf(targetColumn);

    if (targetIndex === -1) {
      return;
    }

    columns.splice(targetIndex, 0, column);
    updateActiveView({ columns });
  }

  function createView() {
    const id = createUuid();
    const view: EmployeeView = {
      ...activeView,
      id,
      name: 'Untitled view',
      filterQuery: normalizeFilterQuery(filterQuery),
      sorts: viewSorts(activeView),
      isDefault: false,
    };

    setViews((currentViews) => [...currentViews, view]);
    setActiveViewId(id);
    setEditingViewId(id);
    setViewBuilderTab('basics');
    setShowViewBuilder(true);
    router.post(
      `/employees/views/${id}`,
      { ...viewPayload(view), _method: 'PUT' },
      { preserveScroll: true },
    );
  }

  function saveActiveView() {
    router.post(
      `/employees/views/${activeView.id}`,
      { ...viewPayload(activeView), _method: 'PUT' },
      { preserveScroll: true },
    );
  }

  function discardActiveViewChanges() {
    if (persistedActiveView === undefined) {
      setViews((currentViews) => currentViews.filter((view) => view.id !== activeView.id));
      setActiveViewId((employeeViews[0] ?? fallbackView).id);
      setFilterQuery((employeeViews[0] ?? fallbackView).filterQuery);

      return;
    }

    updateActiveView(persistedActiveView);
    setFilterQuery(persistedActiveView.filterQuery);
  }

  function updateViewName(name: string) {
    updateActiveView({ name });
  }

  function finishEditingViewName() {
    const name = activeView.name.trim();

    updateActiveView({ name: name === '' ? 'Untitled view' : name });
    setEditingViewId(null);
  }

  function handleViewNameKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Enter') {
      event.currentTarget.blur();
    }
  }

  function viewPayload(view: EmployeeView) {
    const sorts = normalizeSorts(viewSorts(view));

    return {
      name: view.name.trim() || 'Untitled view',
      icon: view.icon,
      filterQuery: normalizeFilterQuery(filterQuery),
      sortColumn: sorts[0].column,
      sortDirection: sorts[0].direction,
      sorts,
      columns: view.columns,
    };
  }

  function sortBy(column: ColumnKey) {
    const currentSorts = viewSorts(activeView);
    const existingSort = currentSorts.find((sort) => sort.column === column);
    const nextDirection = existingSort?.direction === 'asc' ? 'desc' : 'asc';
    const nextSorts = normalizeSorts([
      { column, direction: nextDirection },
      ...currentSorts.filter((sort) => sort.column !== column),
    ]);

    updateActiveView({
      sortColumn: nextSorts[0].column,
      sortDirection: nextSorts[0].direction,
      sorts: nextSorts,
    });
  }

  function updateSortRule(index: number, updates: Partial<SortRule>) {
    const nextSorts = normalizeSorts(
      viewSorts(activeView).map((sort, sortIndex) => (sortIndex === index ? { ...sort, ...updates } : sort)),
    );

    updateActiveView({
      sortColumn: nextSorts[0].column,
      sortDirection: nextSorts[0].direction,
      sorts: nextSorts,
    });
  }

  function addSortRule() {
    const currentSorts = viewSorts(activeView);
    const nextColumn = sortableFields.find((field) => !currentSorts.some((sort) => sort.column === field.key))?.key;

    if (!nextColumn) {
      return;
    }

    const nextSorts = normalizeSorts([...currentSorts, { column: nextColumn, direction: 'asc' }]);

    updateActiveView({
      sortColumn: nextSorts[0].column,
      sortDirection: nextSorts[0].direction,
      sorts: nextSorts,
    });
  }

  function removeSortRule(index: number) {
    const nextSorts = normalizeSorts(viewSorts(activeView).filter((_, sortIndex) => sortIndex !== index));

    updateActiveView({
      sortColumn: nextSorts[0].column,
      sortDirection: nextSorts[0].direction,
      sorts: nextSorts,
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

  function toggleFilterMenu(filterKey: string, event: MouseEvent<HTMLButtonElement>) {
    if (openFilterMenu?.key === filterKey) {
      setOpenFilterMenu(null);

      return;
    }

    if (typeof window === 'undefined') {
      return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const menuWidth = 224;

    setOpenFilterMenu({
      key: filterKey,
      left: Math.min(Math.max(12, rect.left), window.innerWidth - menuWidth - 12),
      top: rect.bottom + 8,
      width: menuWidth,
    });
  }

  function setFilterValues(filterKey: string, values: string[]) {
    updateFilterQuery({ rules: { [filterKey]: values } });
  }

  function toggleFilterValue(filter: (typeof availableFilters)[number], value: string) {
    if (value === 'all') {
      setFilterValues(filter.key, []);

      return;
    }

    const currentFilters = parseFilterQuery(filterQuery);
    const currentValues = selectedFilterValues(currentFilters, filter);

    if (filter.multiple === false) {
      setFilterValues(filter.key, [value]);
      setOpenFilterMenu(null);

      return;
    }

    setFilterValues(
      filter.key,
      currentValues.includes(value)
        ? currentValues.filter((currentValue) => currentValue !== value)
        : [...currentValues, value],
    );
  }

  function toggleSelectedEmployee(id: number) {
    setSelectedEmployeeIds((currentIds) =>
      currentIds.includes(id) ? currentIds.filter((currentId) => currentId !== id) : [...currentIds, id],
    );
  }

  function toggleSelectedPageEmployees() {
    setSelectedEmployeeIds((currentIds) => {
      if (pageEmployeeIds.every((id) => currentIds.includes(id))) {
        return currentIds.filter((id) => !pageEmployeeIds.includes(id));
      }

      return Array.from(new Set([...currentIds, ...pageEmployeeIds]));
    });
  }

  function bulkDeleteSelectedEmployees() {
    if (selectedEmployeeIds.length === 0) {
      return;
    }

    if (!window.confirm(`Delete ${selectedEmployeeIds.length} selected employee records?`)) {
      return;
    }

    router.post(
      '/employees/bulk-delete',
      { employee_ids: selectedEmployeeIds },
      {
        preserveScroll: true,
        onSuccess: () => setSelectedEmployeeIds([]),
      },
    );
  }

  function scrollViews(direction: 'left' | 'right') {
    const strip = document.querySelector<HTMLElement>('[data-employee-view-strip]');

    if (!strip) {
      return;
    }

    strip.scrollBy({
      left: direction === 'left' ? -360 : 360,
      behavior: 'smooth',
    });
  }

  const filteredEmployees = useMemo(() => {
    return employees.filter((employee) => employeeMatchesFilterQuery(employee, filterQuery)).sort((left, right) => {
      for (const sort of viewSorts(activeView)) {
        const direction = sort.direction === 'asc' ? 1 : -1;
        const comparison = sortValue(left, sort.column).localeCompare(sortValue(right, sort.column));

        if (comparison !== 0) {
          return comparison * direction;
        }
      }

      return 0;
    });
  }, [activeView, employees, filterQuery]);

  const viewCounts = useMemo(() => Object.fromEntries(
    views.map((view) => [
      view.id,
      employees.filter((employee) => employeeMatchesFilterQuery(
        employee,
        view.id === activeView.id ? filterQuery : view.filterQuery,
      )).length,
    ]),
  ), [activeView.id, employees, filterQuery, views]);

  const visibleColumns = activeView.columns
    .map((column) => availableColumns.find((field) => field.key === column))
    .filter((column): column is EmployeeField => column !== undefined);
  const mobileColumns = visibleColumns.filter((column) => column.key !== 'employee');
  const ActiveViewIcon = viewIcons[activeView.icon] ?? Eye;
  const filters = parseFilterQuery(filterQuery);
  const openFilter = openFilterMenu
    ? availableFilters.find((filter) => filter.key === openFilterMenu.key) ?? null
    : null;
  const activeFilterCount = [
    filters.text.trim() !== '',
    ...availableFilters.flatMap((filter) => selectedFilterValues(filters, filter).map(() => true)),
  ].filter(Boolean).length;
  const persistedActiveView = employeeViews.find((view) => view.id === activeView.id);
  const activeSorts = viewSorts(activeView);
  const persistedSorts = persistedActiveView ? viewSorts(persistedActiveView) : [];
  const hasUnsavedChanges = persistedActiveView === undefined
    || activeView.name !== persistedActiveView.name
    || activeView.icon !== persistedActiveView.icon
    || JSON.stringify(activeSorts) !== JSON.stringify(persistedSorts)
    || normalizeFilterQuery(filterQuery) !== normalizeFilterQuery(persistedActiveView.filterQuery)
    || activeView.columns.join('|') !== persistedActiveView.columns.join('|');
  const filteredColumnFields = availableColumns.filter((field) =>
    [field.label, field.group, field.description, field.key].join(' ').toLowerCase().includes(columnSearch.trim().toLowerCase()),
  );
  const columnGroups = Array.from(new Set(filteredColumnFields.map((field) => field.group)));
  const totalPages = Math.max(1, Math.ceil(filteredEmployees.length / rowsPerPage));
  const safeCurrentPage = Math.min(currentPage, totalPages);
  const pageStart = (safeCurrentPage - 1) * rowsPerPage;
  const pageEnd = Math.min(pageStart + rowsPerPage, filteredEmployees.length);
  const paginatedEmployees = filteredEmployees.slice(pageStart, pageEnd);
  const pageEmployeeIds = paginatedEmployees.map((employee) => employee.id);
  const allPageEmployeesSelected = pageEmployeeIds.length > 0 && pageEmployeeIds.every((id) => selectedEmployeeIds.includes(id));
  const visibleViewIcons = showAllIcons ? availableViewIcons : availableViewIcons.slice(0, defaultVisibleIconCount);

  return (
    <>
      <Head title={template.title} />
      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="grid min-w-0 gap-4">
          <section className="min-w-0 rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-3 shadow-[0_18px_45px_rgba(23,32,27,0.06)]">
            <div className="flex min-w-0 items-start gap-2">
              <button
                aria-label="Scroll views left"
                className="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-[#17201b]/10 bg-[#fffffb] text-[#59635d] hover:bg-[#f7faf4] disabled:opacity-30"
                type="button"
                onClick={() => scrollViews('left')}
                disabled={!canScrollViewsLeft}
              >
                <ChevronLeft className="size-4" />
              </button>
              <div className="relative min-w-0 flex-1">
                <div
                  className="scrollbar-hidden -mx-1 flex min-w-0 gap-2 overflow-x-auto px-1 pb-1"
                  data-employee-view-strip
                  onScroll={syncViewScrollState}
                >
                  {views.map((view) => (
                    (() => {
                      const ViewIcon = viewIcons[view.icon] ?? Eye;
                      const isActive = view.id === activeView.id;

                      if (isActive) {
                        return (
                          <div
                            data-employee-view-id={view.id}
                            className="inline-flex h-8 shrink-0 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white"
                            key={view.id}
                          >
                            <ViewIcon className="size-4 shrink-0" />
                            {editingViewId === view.id ? (
                              <input
                                aria-label="View name"
                                autoFocus
                                className="min-w-20 max-w-52 bg-transparent font-black text-white outline-none placeholder:text-white/70"
                                size={Math.max(12, Math.min(28, view.name.length + 1))}
                                value={view.name}
                                onBlur={finishEditingViewName}
                                onChange={(event) => updateViewName(event.target.value)}
                                onKeyDown={handleViewNameKeyDown}
                              />
                            ) : (
                              <span className="max-w-52 truncate">{view.name}</span>
                            )}
                            <span className="rounded bg-white/18 px-1.5 py-0.5 text-[0.62rem] font-black text-white">
                              {viewCounts[view.id] ?? 0}
                            </span>
                            <button
                              aria-label="Edit view"
                              className="-mr-1 grid size-5 place-items-center rounded text-white/80 hover:bg-white/15 hover:text-white"
                              type="button"
                              onClick={() => setShowViewBuilder(true)}
                              title="Edit view"
                            >
                              <Pencil className="size-3" />
                            </button>
                          </div>
                        );
                      }

                      return (
                        <button
                          data-employee-view-id={view.id}
                          className="inline-flex h-8 shrink-0 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 text-xs font-black text-[#34423a] hover:bg-[#e8efe8]"
                          key={view.id}
                          type="button"
                          onClick={() => setActiveViewId(view.id)}
                        >
                          <ViewIcon className="size-4 shrink-0" />
                          <span className="truncate">{view.name}</span>
                          <span className="rounded bg-[#17201b]/10 px-1.5 py-0.5 text-[0.62rem] font-black text-[#34423a]">
                            {viewCounts[view.id] ?? 0}
                          </span>
                        </button>
                      );
                    })()
                  ))}
                </div>
              </div>
              <button
                aria-label="Scroll views right"
                className="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-[#17201b]/10 bg-[#fffffb] text-[#59635d] hover:bg-[#f7faf4] disabled:opacity-30"
                type="button"
                onClick={() => scrollViews('right')}
                disabled={!canScrollViewsRight}
              >
                <ChevronRight className="size-4" />
              </button>
              <button
                aria-label="Create view"
                className="inline-flex h-8 shrink-0 items-center gap-2 rounded-md border border-[#27615a]/20 bg-[#fffffb] px-3 text-xs font-black text-[#27615a] hover:border-[#27615a]/40 hover:bg-[#eef7f3]"
                type="button"
                onClick={createView}
                title="Create view"
              >
                <Plus className="size-4" />
                New view
              </button>
            </div>

            <div className="mt-3 flex min-w-0 flex-nowrap items-center gap-2 border-t border-[#17201b]/10 pt-3">
              <div className="flex min-w-0 flex-1 flex-nowrap items-center gap-2 overflow-x-auto overflow-y-visible pb-1">
                <label className="relative flex h-9 w-72 shrink-0 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5">
                  <Search className="size-4 shrink-0 text-[#59635d]" />
                  <input
                    className="min-w-0 flex-1 bg-transparent text-xs font-semibold outline-none"
                    placeholder="Search employees"
                    value={filters.text}
                    onChange={(event) => updateFilterQuery({ text: event.target.value })}
                  />
                  {activeFilterCount > 0 && (
                    <span className="rounded bg-[#17201b]/10 px-1.5 py-0.5 text-[0.62rem] font-black text-[#34423a]">
                      {activeFilterCount}
                    </span>
                  )}
                </label>
                {availableFilters.map((filter) => (
                  <div className="relative shrink-0" key={filter.key}>
                    <button
                      aria-expanded={openFilterMenu?.key === filter.key}
                      data-employee-filter-button
                      className="flex h-9 w-56 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] pl-2.5 pr-3 text-left hover:bg-[#eef7f3]"
                      type="button"
                      onClick={(event) => toggleFilterMenu(filter.key, event)}
                    >
                      <span className="shrink-0 text-[0.62rem] font-black uppercase text-[#8a6a4a]">{filter.label}</span>
                      <span className="min-w-0 flex-1 truncate whitespace-nowrap text-xs font-semibold text-[#17201b]">{filterSummary(filters, filter)}</span>
                    </button>
                  </div>
                ))}
              </div>
              <div className="ml-auto flex shrink-0 items-center gap-2 pb-1">
                <button className="action-button h-9 shrink-0 justify-center whitespace-nowrap px-3" type="button" onClick={() => setShowAdvancedFilters(true)}>
                  <SlidersHorizontal className="size-4" />
                  Advanced filters
                </button>
                <button className="action-button h-9 shrink-0 justify-center whitespace-nowrap px-3" type="button" onClick={discardActiveViewChanges} disabled={!hasUnsavedChanges}>
                  Reset changes
                </button>
                <button className="inline-flex h-9 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-60" type="button" onClick={saveActiveView} disabled={!hasUnsavedChanges}>
                  Save view
                </button>
              </div>
            </div>

          </section>

          {openFilter && openFilterMenu && (
            <div
              className="fixed z-50 grid max-h-72 gap-1 overflow-y-auto rounded-md border border-[#17201b]/10 bg-[#fffffb] p-2 shadow-[0_18px_45px_rgba(23,32,27,0.14)]"
              data-employee-filter-menu
              onMouseDown={(event) => event.stopPropagation()}
              style={{ left: openFilterMenu.left, top: openFilterMenu.top, width: openFilterMenu.width }}
            >
              {openFilter.options.map((option) => {
                const selectedValues = selectedFilterValues(filters, openFilter);
                const isSelected = option.value === 'all' ? selectedValues.length === 0 : selectedValues.includes(option.value);

                return (
                  <label
                    className="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-xs font-semibold text-[#34423a] hover:bg-[#f7faf4]"
                    key={option.value}
                  >
                    <input
                      checked={isSelected}
                      className="size-4 shrink-0 accent-[#27615a]"
                      type="checkbox"
                      onChange={() => toggleFilterValue(openFilter, option.value)}
                    />
                    <span className="truncate whitespace-nowrap">{option.label}</span>
                  </label>
                );
              })}
            </div>
          )}

          {showAdvancedFilters && (
            <div className="fixed inset-0 z-40 flex justify-end bg-[#17201b]/25" onMouseDown={() => setShowAdvancedFilters(false)}>
              <aside
                className="grid h-full w-full max-w-sm grid-rows-[auto_1fr_auto] gap-3 border-l border-[#17201b]/10 bg-[#fffffb] p-4 shadow-[0_24px_70px_rgba(23,32,27,0.24)]"
                onMouseDown={(event) => event.stopPropagation()}
              >
                <div className="flex items-start justify-between gap-3 border-b border-[#17201b]/10 pb-3">
                  <div>
                    <p className="text-sm font-black text-[#17201b]">Advanced filters</p>
                    <p className="mt-1 text-xs font-semibold text-[#59635d]">{activeView.name}</p>
                  </div>
                  <button className="grid size-8 shrink-0 place-items-center rounded-md border border-[#17201b]/10 text-[#59635d] hover:bg-[#f7faf4]" type="button" onClick={() => setShowAdvancedFilters(false)} title="Close filters">
                    <X className="size-4" />
                  </button>
                </div>

                <div className="grid content-start gap-3 overflow-y-auto pr-1">
                  <label className="grid gap-1">
                    <span className="px-1 text-[0.62rem] font-black uppercase text-[#8a6a4a]">Search text</span>
                    <div className="flex h-9 min-w-0 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5">
                      <Search className="size-4 shrink-0 text-[#59635d]" />
                      <input
                        className="min-w-0 flex-1 bg-transparent text-xs font-semibold outline-none"
                        placeholder="e.g. aniket"
                        value={filters.text}
                        onChange={(event) => updateFilterQuery({ text: event.target.value })}
                      />
                    </div>
                  </label>

                  {availableFilters.map((filter) => (
                    <div className="grid gap-1" key={filter.key}>
                      <span className="px-1 text-[0.62rem] font-black uppercase text-[#8a6a4a]">{filter.label}</span>
                      <div className="grid gap-1 rounded-md border border-[#17201b]/10 bg-[#f7faf4] p-1.5">
                        {filter.options.map((option) => {
                          const selectedValues = selectedFilterValues(filters, filter);
                          const isSelected = option.value === 'all' ? selectedValues.length === 0 : selectedValues.includes(option.value);

                          return (
                            <label
                              className="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-xs font-semibold text-[#34423a] hover:bg-[#fffffb]"
                              key={option.value}
                            >
                              <input
                                checked={isSelected}
                                className="size-4 accent-[#27615a]"
                                type="checkbox"
                                onChange={() => toggleFilterValue(filter, option.value)}
                              />
                              <span>{option.label}</span>
                            </label>
                          );
                        })}
                      </div>
                    </div>
                  ))}
                </div>

                <div className="flex justify-end gap-2 border-t border-[#17201b]/10 pt-3">
                  <button className="action-button h-8 justify-center" type="button" onClick={() => setFilterQuery('')} disabled={activeFilterCount === 0}>
                    Clear filters
                  </button>
                  <button className="inline-flex h-8 items-center justify-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white" type="button" onClick={() => setShowAdvancedFilters(false)}>
                    Done
                  </button>
                </div>
              </aside>
            </div>
          )}

          {showViewBuilder && (
            <div className="fixed inset-0 z-40 flex items-start justify-center bg-[#17201b]/25 px-3 py-16" onMouseDown={() => setShowViewBuilder(false)}>
              <div
                className="grid max-h-[calc(100vh-8rem)] w-full max-w-4xl grid-rows-[auto_minmax(0,1fr)_auto] gap-3 overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-3 shadow-[0_24px_70px_rgba(23,32,27,0.24)]"
                onMouseDown={(event) => event.stopPropagation()}
              >
                <div className="flex items-center justify-between gap-3 border-b border-[#17201b]/10 pb-2">
                  <p className="truncate text-sm font-black text-[#17201b]">View settings</p>
                  <button className="grid size-8 shrink-0 place-items-center rounded-md border border-[#17201b]/10 text-[#59635d] hover:bg-[#f7faf4]" type="button" onClick={() => setShowViewBuilder(false)} title="Close">
                    <X className="size-4" />
                  </button>
                </div>

                <div className="grid min-h-0 gap-3">
                  <div className="flex gap-1 rounded-md bg-[#f7faf4] p-1">
                    {[
                      { key: 'basics', label: 'Basics' },
                      { key: 'sort', label: 'Sort' },
                      { key: 'columns', label: 'Columns' },
                    ].map((tab) => (
                      <button
                        className={viewBuilderTab === tab.key ? 'h-8 rounded bg-[#27615a] px-3 text-xs font-black text-white' : 'h-8 rounded px-3 text-xs font-black text-[#59635d] hover:bg-[#fffffb]'}
                        key={tab.key}
                        type="button"
                        onClick={() => setViewBuilderTab(tab.key as ViewBuilderTab)}
                      >
                        {tab.label}
                      </button>
                    ))}
                  </div>

                  <div className="min-h-0 overflow-y-auto pr-1">
                    {viewBuilderTab === 'basics' && (
                      <div className="grid gap-4 md:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
                        <div className="grid content-start gap-3 rounded-md border border-[#17201b]/10 bg-[#f7faf4] p-3">
                          <label className="grid gap-1">
                            <span className="flex h-5 items-center px-1 text-[0.62rem] font-black uppercase text-[#8a6a4a]">View name</span>
                            <input
                              aria-label="View name"
                              className="field h-9 bg-[#fffffb]"
                              placeholder="e.g. Active engineers"
                              value={activeView.name}
                              onBlur={finishEditingViewName}
                              onChange={(event) => updateViewName(event.target.value)}
                              onKeyDown={handleViewNameKeyDown}
                            />
                          </label>
                          <div className="grid gap-1">
                            <p className="text-[0.62rem] font-black uppercase text-[#8a6a4a]">Preview</p>
                            <div className="inline-flex h-9 w-fit max-w-full items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white">
                              <ActiveViewIcon className="size-4 shrink-0" />
                              <span className="truncate">{activeView.name.trim() || 'Untitled view'}</span>
                            </div>
                          </div>
                        </div>

                        <div className="grid content-start gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] p-3">
                          <div className="flex h-5 items-center justify-between gap-3">
                            <p className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">Icon</p>
                            <p className="text-xs font-semibold text-[#59635d]">{availableViewIcons.length} options</p>
                          </div>
                          <div className="grid max-h-48 grid-cols-[repeat(auto-fill,minmax(2rem,1fr))] gap-2 overflow-y-auto pr-1">
                            {visibleViewIcons.map((icon) => {
                              const Icon = viewIcons[icon.key] ?? Eye;

                              return (
                                <button
                                  aria-label={`${icon.label} icon`}
                                  className={activeView.icon === icon.key ? 'grid size-8 place-items-center rounded-md bg-[#27615a] text-white' : 'grid size-8 place-items-center rounded-md border border-[#17201b]/10 bg-[#f7faf4] text-[#34423a] hover:bg-[#e8efe8]'}
                                  key={icon.key}
                                  type="button"
                                  onClick={() => updateActiveView({ icon: icon.key })}
                                  title={`${icon.label} icon`}
                                >
                                  <Icon className="size-4" />
                                </button>
                              );
                            })}
                          </div>
                          <div>
                            {availableViewIcons.length > defaultVisibleIconCount && (
                              <button
                                className="inline-flex h-8 items-center gap-1.5 rounded-md border border-[#17201b]/10 bg-[#fffffb] px-2 text-xs font-black text-[#34423a] hover:bg-[#e8efe8]"
                                type="button"
                                onClick={() => setShowAllIcons((value) => !value)}
                              >
                                <MoreHorizontal className="size-4" />
                                {showAllIcons ? 'Show fewer' : 'More icons'}
                              </button>
                            )}
                          </div>
                        </div>
                      </div>
                    )}

                    {viewBuilderTab === 'sort' && (
                      <div className="grid gap-3">
                        <div className="flex items-center justify-between gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] p-3">
                          <div>
                            <p className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">Sort order</p>
                            <p className="text-xs font-semibold text-[#59635d]">{sortSummary(activeSorts)}</p>
                          </div>
                          <button className="action-button h-8" type="button" onClick={addSortRule} disabled={activeSorts.length >= 3 || activeSorts.length >= sortableFields.length}>
                            <Plus className="size-4" />
                            Add sort
                          </button>
                        </div>
                        <div className="grid gap-2">
                          {activeSorts.map((sort, index) => (
                            <div className="grid gap-2 rounded-md border border-[#17201b]/10 bg-[#fffffb] p-3 md:grid-cols-[8rem_minmax(0,1fr)_12rem_auto] md:items-end" key={`${sort.column}-${index}`}>
                              <div className="grid gap-1">
                                <span className="text-[0.6rem] font-black uppercase text-[#8a6a4a]">Priority</span>
                                <span className="inline-flex h-9 items-center rounded-md bg-[#f7faf4] px-2 text-xs font-black text-[#17201b]">
                                  {index === 0 ? 'Primary' : `Then ${index + 1}`}
                                </span>
                              </div>
                              <label className="grid gap-1">
                                <span className="text-[0.6rem] font-black uppercase text-[#8a6a4a]">Column</span>
                                <select className="field h-9 bg-[#fffffb]" value={sort.column} onChange={(event) => updateSortRule(index, { column: event.target.value as ColumnKey })}>
                                  {sortableFields.map((column) => (
                                    <option key={column.key} value={column.key}>{column.label}</option>
                                  ))}
                                </select>
                              </label>
                              <label className="grid gap-1">
                                <span className="text-[0.6rem] font-black uppercase text-[#8a6a4a]">Direction</span>
                                <select className="field h-9 bg-[#fffffb]" value={sort.direction} onChange={(event) => updateSortRule(index, { direction: event.target.value as 'asc' | 'desc' })}>
                                  <option value="asc">Ascending</option>
                                  <option value="desc">Descending</option>
                                </select>
                              </label>
                              <button className="grid size-9 shrink-0 place-items-center rounded-md border border-[#17201b]/10 bg-[#fffffb] text-[#59635d] hover:bg-[#e8efe8] disabled:opacity-30" type="button" onClick={() => removeSortRule(index)} disabled={activeSorts.length === 1} title="Remove sort">
                                <X className="size-3.5" />
                              </button>
                            </div>
                          ))}
                          {activeSorts.length < 3 && activeSorts.length < sortableFields.length && (
                            <button className="flex h-11 items-center justify-center gap-2 rounded-md border border-dashed border-[#27615a]/30 bg-[#f7faf4] text-xs font-black text-[#27615a] hover:bg-[#eef7f3]" type="button" onClick={addSortRule}>
                              <Plus className="size-4" />
                              Add another sort
                            </button>
                          )}
                        </div>
                      </div>
                    )}

                    {viewBuilderTab === 'columns' && (
                      <div className="grid gap-4 lg:grid-cols-[minmax(16rem,0.8fr)_minmax(0,1.2fr)]">
                        <div className="grid content-start gap-2">
                          <div className="flex h-5 items-center justify-between gap-3">
                            <p className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">Selected order</p>
                            <p className="text-xs font-semibold text-[#59635d]">{visibleColumns.length} selected</p>
                          </div>
                          <div className="grid max-h-[44vh] gap-2 overflow-y-auto pr-1">
                            {visibleColumns.map((column, index) => (
                              <div
                                className="flex items-center gap-2 rounded-md border border-[#27615a]/20 bg-[#eef7f3] p-2"
                                draggable
                                key={column.key}
                                onDragOver={(event) => event.preventDefault()}
                                onDragStart={(event) => {
                                  event.dataTransfer.effectAllowed = 'move';
                                  event.dataTransfer.setData('text/plain', column.key);
                                }}
                                onDrop={(event) => {
                                  event.preventDefault();
                                  reorderColumn(event.dataTransfer.getData('text/plain') as ColumnKey, column.key);
                                }}
                              >
                                <span className="grid size-6 shrink-0 cursor-grab place-items-center rounded bg-[#27615a]/10 text-[#27615a]" title="Drag to reorder">
                                  <GripVertical className="size-4" />
                                </span>
                                <span className="grid size-6 shrink-0 place-items-center rounded bg-[#27615a]/10 text-[0.62rem] font-black text-[#27615a]">
                                  {index + 1}
                                </span>
                                <span className="min-w-0 flex-1 truncate text-xs font-black text-[#17201b]">{column.label}</span>
                                <button className="grid size-7 shrink-0 place-items-center rounded border border-[#17201b]/10 bg-[#fffffb] text-[#59635d] disabled:opacity-30" type="button" onClick={() => moveColumn(column.key, 'up')} disabled={index === 0} title="Move column up">
                                  <ChevronUp className="size-4" />
                                </button>
                                <button className="grid size-7 shrink-0 place-items-center rounded border border-[#17201b]/10 bg-[#fffffb] text-[#59635d] disabled:opacity-30" type="button" onClick={() => moveColumn(column.key, 'down')} disabled={index === visibleColumns.length - 1} title="Move column down">
                                  <ChevronDown className="size-4" />
                                </button>
                              </div>
                            ))}
                          </div>
                        </div>

                        <div className="grid min-w-0 content-start gap-3">
                          <div className="grid gap-1">
                            <div className="flex h-5 items-center justify-between gap-3">
                              <p className="text-[0.68rem] font-black uppercase text-[#7a5a3a]">Available columns</p>
                              <p className="text-xs font-semibold text-[#59635d]">Toggle visibility</p>
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
                          </div>

                          <div className="grid max-h-[44vh] gap-3 overflow-y-auto pr-1">
                            {columnGroups.map((group) => (
                              <div className="grid gap-2" key={group}>
                                <p className="text-[0.65rem] font-black uppercase text-[#7a5a3a]">{group}</p>
                                <div className="grid gap-2 sm:grid-cols-2">
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
                    )}
                  </div>
                </div>

                <div className="flex items-center justify-between gap-2 border-t border-[#17201b]/10 bg-[#fffffb] pt-2">
                  {!activeView.isDefault ? (
                    <button className="inline-flex h-8 items-center justify-center gap-2 rounded-md border border-[#a33b2f]/20 px-3 text-xs font-black text-[#a33b2f] hover:bg-[#fff4f1]" type="button" onClick={deleteActiveView}>
                      <Trash2 className="size-4" />
                      Delete view
                    </button>
                  ) : (
                    <span className="rounded bg-[#f7faf4] px-2 py-1 text-[0.65rem] font-black uppercase text-[#59635d]">
                      Default view
                    </span>
                  )}
                  <div className="flex justify-end gap-2">
                    <button className="action-button h-8 justify-center" type="button" onClick={discardActiveViewChanges} disabled={!hasUnsavedChanges}>
                      Discard
                    </button>
                    <button className="inline-flex h-8 items-center justify-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-60" type="button" onClick={saveActiveView} disabled={!hasUnsavedChanges}>
                      Save
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          <section className="min-w-0 overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] shadow-[0_18px_45px_rgba(23,32,27,0.07)]">
            <div className="grid gap-3 border-b border-[#17201b]/10 px-4 py-3">
              <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                  <p className="flex items-center gap-2 text-sm font-black text-[#17201b]">
                    <ActiveViewIcon className="size-4 text-[#27615a]" />
                    {activeView.name}
                  </p>
                  <p className="text-xs text-[#59635d]">
                    {filteredEmployees.length === 0 ? '0' : `${pageStart + 1}-${pageEnd}`} of {filteredEmployees.length} people shown
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2 xl:justify-end">
                  <span className="rounded bg-[#f7faf4] px-2 py-1 text-[0.65rem] font-black uppercase text-[#59635d]">
                    {visibleColumns.length} columns
                  </span>
                  <span className="rounded bg-[#f7faf4] px-2 py-1 text-[0.65rem] font-black uppercase text-[#59635d]">
                    {activeSorts.length} {activeSorts.length === 1 ? 'sort' : 'sorts'}
                  </span>
                  <span className="rounded bg-[#f7faf4] px-2 py-1 text-[0.65rem] font-black uppercase text-[#59635d]">
                    {activeFilterCount} {activeFilterCount === 1 ? 'filter' : 'filters'}
                  </span>
                </div>
              </div>

              <div className="flex flex-col gap-2 border-t border-[#17201b]/10 pt-3 xl:flex-row xl:items-center xl:justify-between">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="rounded bg-[#17201b]/10 px-2 py-1 text-[0.65rem] font-black text-[#34423a]">
                    {selectedEmployeeIds.length} selected
                  </span>
                  <button className="action-button h-8" type="button" disabled title="Invite selected employees">
                    <MailCheck className="size-4" />
                    Invite
                  </button>
                  <button className="action-button h-8" type="button" disabled title="Disable selected employee access">
                    <UserRoundX className="size-4" />
                    Disable
                  </button>
                  <button className="action-button h-8 text-[#a33b2f]" type="button" disabled={selectedEmployeeIds.length === 0} onClick={bulkDeleteSelectedEmployees} title="Delete selected employees">
                    <Trash2 className="size-4" />
                    Delete
                  </button>
                </div>

                <div className="flex flex-wrap items-center gap-2 xl:justify-end">
                  <button className="action-button h-8" type="button" disabled title="Import employees">
                    <Upload className="size-4" />
                    Import
                  </button>
                  <button className="action-button h-8" type="button" disabled title="Export employees">
                    <Download className="size-4" />
                    Export
                  </button>
                  <Link className="inline-flex h-8 shrink-0 items-center justify-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white no-underline" href="/employees/create">
                    <Plus className="size-4" />
                    Add Employee
                  </Link>
                </div>
              </div>
            </div>

            <div className="divide-y divide-[#17201b]/10 md:hidden">
              {paginatedEmployees.map((employee) => {
                const accessStatus = employee.access_status || 'not_invited';
                const isSelected = selectedEmployeeIds.includes(employee.id);

                return (
                  <div className="grid gap-3 px-4 py-3" key={employee.id}>
                    <div className="flex min-w-0 items-start justify-between gap-3">
                      <div className="flex min-w-0 items-start gap-2">
                        <input
                          className="mt-2 size-4 shrink-0 accent-[#27615a]"
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => toggleSelectedEmployee(employee.id)}
                          aria-label={`Select ${displayName(employee)}`}
                        />
                        {columnValue(employee, 'employee')}
                      </div>
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
              <table className="w-max min-w-full text-left text-sm">
                <thead className="bg-[#f7faf4] text-[0.68rem] font-black uppercase text-[#7a5a3a]">
                  <tr>
                    <th className="w-10 whitespace-nowrap px-4 py-2.5">
                      <input
                        className="size-4 accent-[#27615a]"
                        type="checkbox"
                        checked={allPageEmployeesSelected}
                        onChange={toggleSelectedPageEmployees}
                        aria-label="Select employees on this page"
                      />
                    </th>
                    {visibleColumns.map((column) => (
                      <th className={column.key === 'employee' ? 'min-w-64 whitespace-nowrap px-4 py-2.5' : 'min-w-44 whitespace-nowrap px-4 py-2.5'} key={column.key}>
                        <button className="inline-flex items-center gap-1.5 whitespace-nowrap text-left" type="button" onClick={() => sortBy(column.key)}>
                          {column.label}
                          {activeSorts.some((sort) => sort.column === column.key) && (
                            <>
                              {activeSorts.find((sort) => sort.column === column.key)?.direction === 'asc' ? <ArrowDownAZ className="size-3.5" /> : <ArrowUpAZ className="size-3.5" />}
                              <span className="rounded bg-[#17201b]/10 px-1 text-[0.58rem]">
                                {(activeSorts.findIndex((sort) => sort.column === column.key) + 1).toString()}
                              </span>
                            </>
                          )}
                        </button>
                      </th>
                    ))}
                    <th className="sticky right-0 z-20 min-w-32 whitespace-nowrap border-l border-[#17201b]/10 bg-[#f7faf4] px-4 py-2.5 text-right shadow-[-10px_0_18px_rgba(23,32,27,0.04)]">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#17201b]/10">
                  {paginatedEmployees.map((employee) => (
                    <tr className="group hover:bg-[#f7faf4]" key={employee.id}>
                      <td className="whitespace-nowrap px-4 py-3">
                        <input
                          className="size-4 accent-[#27615a]"
                          type="checkbox"
                          checked={selectedEmployeeIds.includes(employee.id)}
                          onChange={() => toggleSelectedEmployee(employee.id)}
                          aria-label={`Select ${displayName(employee)}`}
                        />
                      </td>
                      {visibleColumns.map((column) => (
                        <td className={column.key === 'employee' ? 'min-w-64 whitespace-nowrap px-4 py-3 text-xs font-semibold text-[#59635d]' : 'min-w-44 whitespace-nowrap px-4 py-3 text-xs font-semibold text-[#59635d]'} key={column.key}>
                          {columnValue(employee, column.key)}
                        </td>
                      ))}
                      <td className="sticky right-0 z-10 min-w-32 whitespace-nowrap border-l border-[#17201b]/10 bg-[#fffffb] px-4 py-3 shadow-[-10px_0_18px_rgba(23,32,27,0.04)] group-hover:bg-[#f7faf4]">
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

            {filteredEmployees.length > 0 && (
              <div className="flex flex-col gap-2 border-t border-[#17201b]/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2 text-xs font-semibold text-[#59635d]">
                  <span>Rows</span>
                  <select className="field h-8 w-20" value={rowsPerPage} onChange={(event) => setRowsPerPage(Number(event.target.value))}>
                    {[10, 25, 50].map((count) => (
                      <option key={count} value={count}>{count}</option>
                    ))}
                  </select>
                </div>
                <div className="flex items-center justify-between gap-3 sm:justify-end">
                  <p className="text-xs font-semibold text-[#59635d]">
                    Page {safeCurrentPage} of {totalPages}
                  </p>
                  <div className="flex gap-2">
                    <button className="grid size-8 place-items-center rounded-md border border-[#17201b]/10 text-[#34423a] disabled:opacity-40" type="button" onClick={() => setCurrentPage((page) => Math.max(1, page - 1))} disabled={safeCurrentPage === 1} title="Previous page">
                      <ChevronLeft className="size-4" />
                    </button>
                    <button className="grid size-8 place-items-center rounded-md border border-[#17201b]/10 text-[#34423a] disabled:opacity-40" type="button" onClick={() => setCurrentPage((page) => Math.min(totalPages, page + 1))} disabled={safeCurrentPage === totalPages} title="Next page">
                      <ChevronRight className="size-4" />
                    </button>
                  </div>
                </div>
              </div>
            )}
          </section>
        </div>
      </AppLayout>

      <ConfirmDialog employee={employeeToDelete} onCancel={() => setEmployeeToDelete(null)} />
    </>
  );
}
