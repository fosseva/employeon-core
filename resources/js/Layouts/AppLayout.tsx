import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
  Bell,
  Building2,
  CalendarDays,
  ChevronDown,
  ChevronsLeft,
  ChevronsRight,
  CircleDollarSign,
  ClipboardList,
  Gauge,
  KeyRound,
  LifeBuoy,
  LogOut,
  Search,
  Settings,
  ShieldCheck,
  UsersRound,
  UserRound,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ComponentType, ReactNode } from 'react';

type User = {
  name: string;
  email: string;
  role: string;
};

type MenuItem = {
  label: string;
  href: string;
  icon: string;
  group: 'Workforce' | 'Operations' | 'Admin';
};

const iconMap: Record<string, ComponentType<{ className?: string }>> = {
  building: Building2,
  calendar: CalendarDays,
  clipboard: ClipboardList,
  dollar: CircleDollarSign,
  gauge: Gauge,
  key: KeyRound,
  'life-buoy': LifeBuoy,
  settings: Settings,
  shield: ShieldCheck,
  users: UsersRound,
};

const notifications = [
  '3 leave requests need review',
  'Payroll draft closes tomorrow',
  'New employee profile pending approval',
];

type AppLayoutProps = {
  children: ReactNode;
  title: string;
  subtitle?: string;
  user: User;
};

function initials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function SidebarLink({ collapsed, item }: { collapsed: boolean; item: MenuItem }) {
  const Icon = iconMap[item.icon] ?? ClipboardList;
  const active = typeof window !== 'undefined' && window.location.pathname === item.href;
  const className = collapsed
    ? active
      ? 'grid size-10 place-items-center overflow-visible rounded-md bg-[#27615a] text-white'
      : 'grid size-10 place-items-center overflow-visible rounded-md text-[#3e4b44] hover:bg-[#e8efe8] hover:text-[#17201b]'
    : active
      ? 'flex h-8 items-center gap-2 rounded-md bg-[#27615a] px-2 text-[0.72rem] font-bold text-white'
      : 'flex h-8 items-center gap-2 rounded-md px-2 text-[0.72rem] font-semibold text-[#3e4b44] hover:bg-[#e8efe8] hover:text-[#17201b]';

  return (
    <Link className={className} href={item.href} title={collapsed ? item.label : undefined}>
      <Icon className={collapsed ? 'h-5 w-5 shrink-0 overflow-visible' : 'size-4 shrink-0'} />
      {!collapsed && <span className="truncate">{item.label}</span>}
      {active && !collapsed && <span className="ml-auto size-1.5 rounded-full bg-white" />}
    </Link>
  );
}

export default function AppLayout({ children, title, subtitle, user }: AppLayoutProps) {
  const page = usePage<{ employeon?: { navigation?: MenuItem[] } }>();
  const menuItems = page.props.employeon?.navigation ?? [];
  const [collapsed, setCollapsed] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [showNotifications, setShowNotifications] = useState(false);
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const searchInputRef = useRef<HTMLInputElement>(null);
  const { post, processing } = useForm();

  const groupedMenu = useMemo(
    () =>
      menuItems.reduce<Record<MenuItem['group'], MenuItem[]>>(
        (groups, item) => {
          groups[item.group].push(item);
          return groups;
        },
        { Workforce: [], Operations: [], Admin: [] },
      ),
    [],
  );

  const searchResults = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();

    if (query.length === 0) {
      return [];
    }

    return menuItems
      .filter((item) => `${item.label} ${item.group}`.toLowerCase().includes(query))
      .slice(0, 6);
  }, [searchQuery]);

  useEffect(() => {
    function isTypingTarget(target: EventTarget | null) {
      if (!(target instanceof HTMLElement)) {
        return false;
      }

      return (
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.isContentEditable
      );
    }

    function focusSearch(event: KeyboardEvent) {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchInputRef.current?.focus();
        searchInputRef.current?.select();
        return;
      }

      if (
        event.key === '/' &&
        !event.metaKey &&
        !event.ctrlKey &&
        !event.altKey &&
        !isTypingTarget(event.target)
      ) {
        event.preventDefault();
        searchInputRef.current?.focus();
      }
    }

    window.addEventListener('keydown', focusSearch);

    return () => window.removeEventListener('keydown', focusSearch);
  }, []);

  function openFirstSearchResult() {
    const [firstResult] = searchResults;

    if (firstResult) {
      router.visit(firstResult.href);
      setSearchQuery('');
    }
  }

  function logout() {
    post('/logout');
  }

  return (
    <main className="min-h-screen bg-[#f4f7f1] text-[#17201b]">
      <div
        className={
          collapsed
            ? 'grid min-h-screen min-w-0 xl:grid-cols-[64px_1fr]'
            : 'grid min-h-screen min-w-0 xl:grid-cols-[224px_1fr]'
        }
      >
        <aside className="min-w-0 border-b border-[#17201b]/10 bg-[#fffffb] xl:sticky xl:top-0 xl:h-screen xl:border-b-0 xl:border-r">
          <div className={collapsed ? 'flex h-full min-w-0 flex-col items-center px-2 py-3' : 'flex h-full min-w-0 flex-col px-2.5 py-3'}>
            <div className={collapsed ? 'mb-4 grid justify-items-center gap-2' : 'mb-3 flex items-center justify-between gap-2'}>
              <Link
                className={
                  collapsed
                    ? 'grid size-9 place-items-center rounded-md bg-[#27615a] text-sm font-black text-white no-underline'
                    : 'grid gap-0.5 px-1 no-underline'
                }
                href="/profile"
              >
                {collapsed ? (
                  'E'
                ) : (
                  <>
                    <span className="text-[0.68rem] font-extrabold uppercase text-[#27615a]">
                      Employeon
                    </span>
                    <span className="text-base font-black text-[#17201b]">People Ops</span>
                  </>
                )}
              </Link>

              <button
                className={
                  collapsed
                    ? 'hidden size-7 place-items-center rounded-md border border-[#17201b]/10 bg-[#fffffb] text-[#59635d] hover:bg-[#e8efe8] focus:outline-none focus:ring-2 focus:ring-[#27615a]/25 xl:grid'
                    : 'hidden size-7 place-items-center rounded-md border border-[#17201b]/10 bg-[#f7faf4] text-[#34423a] hover:bg-[#e8efe8] focus:outline-none focus:ring-2 focus:ring-[#27615a]/25 xl:grid'
                }
                type="button"
                aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                onClick={() => setCollapsed((value) => !value)}
              >
                {collapsed ? (
                  <ChevronsRight className="size-4" />
                ) : (
                  <ChevronsLeft className="size-4" />
                )}
              </button>
            </div>

            <nav className={collapsed ? 'grid min-w-0 justify-items-center gap-3 overflow-y-auto pb-0' : 'flex min-w-0 gap-2 overflow-x-auto pb-1 xl:grid xl:overflow-y-auto xl:pb-0'} aria-label="Workspace navigation">
              {(Object.keys(groupedMenu) as MenuItem['group'][]).map((group) => {
                const items = groupedMenu[group];

                if (items.length === 0) {
                  return null;
                }

                return (
                  <div className={collapsed ? 'grid justify-items-center gap-2' : 'flex shrink-0 gap-1 xl:grid xl:gap-0.5'} key={group}>
                    {!collapsed && (
                      <p className="px-2 py-1 text-[0.6rem] font-black uppercase text-[#8a6a4a]">
                        {group}
                      </p>
                    )}
                    {items.map((item) => (
                      <SidebarLink collapsed={collapsed} item={item} key={item.label} />
                    ))}
                  </div>
                );
              })}
            </nav>
          </div>
        </aside>

        <section className="min-w-0 overflow-x-hidden">
          <header className="sticky top-0 z-10 border-b border-[#17201b]/10 bg-[#fffffb]/95 px-3 py-2 backdrop-blur sm:px-4 xl:px-5">
            <div className="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
              <div className="min-w-0">
                <p className="text-[0.6rem] font-extrabold uppercase text-[#27615a]">Workspace</p>
                <h1 className="truncate text-lg font-black leading-tight text-[#17201b]">
                  {title}
                </h1>
                {subtitle && <p className="truncate text-[0.72rem] text-[#59635d]">{subtitle}</p>}
              </div>

              <div className="flex flex-wrap items-center gap-2">
                <div className="relative min-w-0 flex-[1_1_180px] xl:w-[300px] xl:flex-none">
                  <label className="flex h-8 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5 text-sm text-[#59635d] focus-within:border-[#27615a]/40 focus-within:bg-[#fffffb]">
                    <Search className="size-4 shrink-0" />
                    <input
                      ref={searchInputRef}
                      className="min-w-0 flex-1 bg-transparent text-xs font-semibold text-[#17201b] outline-none placeholder:text-[#8a948d]"
                      type="search"
                      value={searchQuery}
                      placeholder="Search menus"
                      onChange={(event) => setSearchQuery(event.target.value)}
                      onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                          event.preventDefault();
                          openFirstSearchResult();
                        }

                        if (event.key === 'Escape') {
                          setSearchQuery('');
                          event.currentTarget.blur();
                        }
                      }}
                    />
                    <kbd className="hidden rounded border border-[#17201b]/10 bg-[#fffffb] px-1.5 py-0.5 text-[0.58rem] font-black text-[#59635d] sm:inline">
                      Cmd K
                    </kbd>
                  </label>

                  {searchQuery.trim().length > 0 && (
                    <div className="absolute left-0 right-0 mt-2 overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] shadow-[0_18px_45px_rgba(23,32,27,0.12)]">
                      {searchResults.length > 0 ? (
                        searchResults.map((item) => {
                          const Icon = iconMap[item.icon] ?? ClipboardList;

                          return (
                            <Link
                              className="flex items-center gap-2 px-3 py-2.5 text-xs font-bold text-[#34423a] no-underline hover:bg-[#f7faf4]"
                              href={item.href}
                              key={item.href}
                              onClick={() => setSearchQuery('')}
                            >
                              <Icon className="size-4 text-[#27615a]" />
                              <span className="min-w-0 flex-1 truncate">{item.label}</span>
                              <span className="text-[0.65rem] font-black uppercase text-[#8a6a4a]">
                                {item.group}
                              </span>
                            </Link>
                          );
                        })
                      ) : (
                        <p className="px-3 py-2.5 text-xs font-semibold text-[#59635d]">
                          No menus found
                        </p>
                      )}
                    </div>
                  )}
                </div>

                <div className="relative">
                  <button
                    className="relative grid size-8 place-items-center rounded-md border border-[#17201b]/10 bg-[#fffffb] text-[#34423a] hover:bg-[#f7faf4]"
                    type="button"
                    aria-label="Notifications"
                    onClick={() => setShowNotifications((value) => !value)}
                  >
                    <Bell className="size-4" />
                    <span className="absolute right-1.5 top-1.5 size-1.5 rounded-full bg-[#a33b2f]" />
                  </button>

                  {showNotifications && (
                    <div className="absolute right-0 mt-2 w-[min(20rem,calc(100vw-1.5rem))] rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-3 shadow-[0_18px_45px_rgba(23,32,27,0.12)]">
                      <p className="px-1 text-xs font-black uppercase text-[#27615a]">
                        Notifications
                      </p>
                      <div className="mt-2 grid gap-2">
                        {notifications.map((notification) => (
                          <div
                            className="rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-3 py-2 text-xs font-semibold text-[#34423a]"
                            key={notification}
                          >
                            {notification}
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>

                <div className="relative">
                  <button
                    className="flex h-8 items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#fffffb] px-2 text-left hover:bg-[#f7faf4]"
                    type="button"
                    onClick={() => setShowProfileMenu((value) => !value)}
                  >
                    <span className="grid size-6 place-items-center rounded bg-[#27615a] text-[0.65rem] font-black text-white">
                      {initials(user.name)}
                    </span>
                    <span className="hidden min-w-0 sm:block">
                      <span className="block truncate text-xs font-black text-[#17201b]">
                        {user.name}
                      </span>
                      <span className="block truncate text-[0.68rem] font-semibold text-[#59635d]">
                        {user.role}
                      </span>
                    </span>
                    <ChevronDown className="size-3.5 text-[#59635d]" />
                  </button>

                  {showProfileMenu && (
                    <div className="absolute right-0 mt-2 w-[min(16rem,calc(100vw-1.5rem))] rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-3 shadow-[0_18px_45px_rgba(23,32,27,0.12)]">
                      <div className="border-b border-[#17201b]/10 pb-3">
                        <p className="text-sm font-black text-[#17201b]">{user.name}</p>
                        <p className="mt-0.5 truncate text-xs text-[#59635d]">{user.email}</p>
                      </div>
                      <Link
                        className="mt-2 flex h-9 items-center gap-2 rounded-md px-2 text-xs font-bold text-[#34423a] no-underline hover:bg-[#f7faf4]"
                        href="/profile"
                      >
                        <UserRound className="size-4" />
                        Profile
                      </Link>
                      <button
                        className="flex h-9 w-full items-center gap-2 rounded-md px-2 text-left text-xs font-bold text-[#a33b2f] hover:bg-[#fff4f1] disabled:opacity-70"
                        type="button"
                        disabled={processing}
                        onClick={logout}
                      >
                        <LogOut className="size-4" />
                        {processing ? 'Logging out...' : 'Logout'}
                      </button>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </header>

          <div className="px-3 py-3 sm:px-4 sm:py-4 xl:px-5">{children}</div>
        </section>
      </div>
    </main>
  );
}
