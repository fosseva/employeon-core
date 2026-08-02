import { Head, router, useForm } from '@inertiajs/react';
import { CalendarDays, Clock, Plus, Save, Search, Trash2, UserCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

type User = { name: string; email: string; role: string };
type Template = { title: string; subtitle: string };
type EmployeeOption = { id: number; name: string; work_email: string };
type AttendanceEntry = {
  id: number;
  employee_id: number;
  employee_name: string;
  attendance_date: string;
  status: string;
  check_in_at: string;
  check_out_at: string;
  notes: string;
};

type Props = {
  user: User;
  template: Template;
  employees: EmployeeOption[];
  entries: AttendanceEntry[];
  stats: { present: number; absent: number; on_leave: number };
};

const blankEntry = {
  employee_id: '',
  attendance_date: new Date().toISOString().slice(0, 10),
  status: 'present',
  check_in_at: '',
  check_out_at: '',
  notes: '',
};

function statusClass(status: string) {
  if (status === 'present') {
    return 'border-[#27615a]/20 bg-[#eef7f3] text-[#27615a]';
  }

  if (status === 'absent') {
    return 'border-[#a33b2f]/20 bg-[#fff4f1] text-[#a33b2f]';
  }

  return 'border-[#9a6a2f]/20 bg-[#fff8e9] text-[#8a5a1f]';
}

function formatStatus(status: string) {
  return status.replace('_', ' ');
}

function ConfirmDialog({
  entry,
  onCancel,
}: {
  entry: AttendanceEntry | null;
  onCancel: () => void;
}) {
  if (!entry) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-[#17201b]/30 px-4">
      <div className="w-full max-w-md rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5 shadow-[0_24px_70px_rgba(23,32,27,0.22)]">
        <p className="text-sm font-black text-[#17201b]">Delete attendance entry?</p>
        <p className="mt-2 text-sm leading-6 text-[#59635d]">
          Remove {entry.employee_name}'s {entry.attendance_date} attendance record.
        </p>
        <div className="mt-5 flex justify-end gap-2">
          <button className="action-button" type="button" onClick={onCancel}>
            Cancel
          </button>
          <button
            className="inline-flex h-8 items-center gap-2 rounded-md bg-[#a33b2f] px-3 text-xs font-black text-white"
            type="button"
            onClick={() => {
              router.post(`/attendance/${entry.id}`, { _method: 'DELETE' }, { preserveScroll: true });
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

function AttendanceForm({ employees, entry }: { employees: EmployeeOption[]; entry?: AttendanceEntry }) {
  const form = useForm(
    entry
      ? {
          employee_id: String(entry.employee_id),
          attendance_date: entry.attendance_date,
          status: entry.status,
          check_in_at: entry.check_in_at,
          check_out_at: entry.check_out_at,
          notes: entry.notes,
        }
      : blankEntry,
  );
  const isEditing = Boolean(entry);

  return (
    <form
      className="grid gap-3"
      onSubmit={(event) => {
        event.preventDefault();

        if (entry) {
          form.transform((data) => ({ ...data, _method: 'PUT' }));
          form.post(`/attendance/${entry.id}`, { preserveScroll: true });
          return;
        }

        form.post('/attendance', {
          preserveScroll: true,
          onSuccess: () => form.reset(),
        });
      }}
    >
      <div className="grid gap-2">
        <select className="field" value={form.data.employee_id} onChange={(event) => form.setData('employee_id', event.target.value)}>
          <option value="">Select employee</option>
          {employees.map((employee) => (
            <option value={employee.id} key={employee.id}>{employee.name}</option>
          ))}
        </select>
        <div className="grid gap-2 sm:grid-cols-2">
          <input className="field" type="date" value={form.data.attendance_date} onChange={(event) => form.setData('attendance_date', event.target.value)} />
          <select className="field" value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}>
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="on_leave">On leave</option>
          </select>
        </div>
        <div className="grid gap-2 sm:grid-cols-2">
          <input className="field" type="datetime-local" value={form.data.check_in_at} onChange={(event) => form.setData('check_in_at', event.target.value)} />
          <input className="field" type="datetime-local" value={form.data.check_out_at} onChange={(event) => form.setData('check_out_at', event.target.value)} />
        </div>
        <input className="field" placeholder="Notes" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
      </div>

      <div className="flex justify-end">
        <button
          className="inline-flex h-8 items-center gap-2 rounded-md bg-[#27615a] px-3 text-xs font-black text-white disabled:opacity-70"
          type="submit"
          disabled={form.processing || employees.length === 0}
        >
          {isEditing ? <Save className="size-4" /> : <Plus className="size-4" />}
          {isEditing ? 'Save Entry' : 'Add Entry'}
        </button>
      </div>
    </form>
  );
}

export default function AttendanceIndex({ user, template, employees, entries, stats }: Props) {
  const [query, setQuery] = useState('');
  const [selectedEntryId, setSelectedEntryId] = useState<number | null>(entries[0]?.id ?? null);
  const [entryToDelete, setEntryToDelete] = useState<AttendanceEntry | null>(null);

  const filteredEntries = useMemo(() => {
    const term = query.trim().toLowerCase();

    if (term === '') {
      return entries;
    }

    return entries.filter((entry) =>
      [
        entry.employee_name,
        entry.attendance_date,
        entry.status,
        entry.notes,
      ].join(' ').toLowerCase().includes(term),
    );
  }, [entries, query]);

  const selectedEntry = entries.find((entry) => entry.id === selectedEntryId) ?? filteredEntries[0];

  return (
    <>
      <Head title={template.title} />
      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="grid gap-4">
          <section className="grid gap-3 md:grid-cols-3">
            <div className="metric"><UserCheck className="size-4 text-[#27615a]" /><span>{stats.present}</span><p>Present</p></div>
            <div className="metric"><Clock className="size-4 text-[#27615a]" /><span>{stats.absent}</span><p>Absent</p></div>
            <div className="metric"><CalendarDays className="size-4 text-[#27615a]" /><span>{stats.on_leave}</span><p>On leave</p></div>
          </section>

          <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div className="overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] shadow-[0_18px_45px_rgba(23,32,27,0.07)]">
              <div className="flex flex-col gap-3 border-b border-[#17201b]/10 px-4 py-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <p className="text-sm font-black text-[#17201b]">Attendance log</p>
                  <p className="text-xs text-[#59635d]">{filteredEntries.length} entries shown</p>
                </div>
                <label className="flex h-8 min-w-[220px] items-center gap-2 rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-2.5">
                  <Search className="size-4 text-[#59635d]" />
                  <input
                    className="min-w-0 flex-1 bg-transparent text-xs font-semibold outline-none"
                    placeholder="Search attendance"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                  />
                </label>
              </div>

              <div className="divide-y divide-[#17201b]/10">
                {filteredEntries.map((entry) => (
                  <div className={selectedEntry?.id === entry.id ? 'grid gap-3 bg-[#eef7f3] px-4 py-3 lg:grid-cols-[1fr_auto]' : 'grid gap-3 px-4 py-3 hover:bg-[#f7faf4] lg:grid-cols-[1fr_auto]'} key={entry.id}>
                    <button className="min-w-0 text-left" type="button" onClick={() => setSelectedEntryId(entry.id)}>
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="font-black text-[#17201b]">{entry.employee_name}</p>
                        <span className={`rounded border px-2 py-1 text-[0.68rem] font-black uppercase ${statusClass(entry.status)}`}>{formatStatus(entry.status)}</span>
                      </div>
                      <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-[#59635d]">
                        <span>{entry.attendance_date}</span>
                        <span>In: {entry.check_in_at || '-'}</span>
                        <span>Out: {entry.check_out_at || '-'}</span>
                      </div>
                      {entry.notes && <p className="mt-2 text-xs text-[#59635d]">{entry.notes}</p>}
                    </button>
                    <div className="flex items-center justify-end gap-2">
                      <button className="action-button" type="button" onClick={() => setSelectedEntryId(entry.id)}>Edit</button>
                      <button className="grid size-8 place-items-center rounded-md border border-[#a33b2f]/20 text-[#a33b2f] hover:bg-[#fff4f1]" type="button" onClick={() => setEntryToDelete(entry)} title="Delete entry">
                        <Trash2 className="size-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              {filteredEntries.length === 0 && (
                <p className="px-4 py-10 text-center text-sm font-semibold text-[#59635d]">
                  No attendance entries match your search.
                </p>
              )}
            </div>

            <aside className="grid content-start gap-4">
              <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4 shadow-[0_18px_45px_rgba(23,32,27,0.07)]">
                <div className="mb-3">
                  <p className="text-sm font-black text-[#17201b]">Quick entry</p>
                  <p className="text-xs text-[#59635d]">Record today or backfill a day.</p>
                </div>
                <AttendanceForm employees={employees} />
              </div>

              {selectedEntry && (
                <div className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4 shadow-[0_18px_45px_rgba(23,32,27,0.07)]">
                  <div className="mb-3">
                    <p className="text-sm font-black text-[#17201b]">Edit entry</p>
                    <p className="text-xs text-[#59635d]">{selectedEntry.employee_name} on {selectedEntry.attendance_date}</p>
                  </div>
                  <AttendanceForm employees={employees} entry={selectedEntry} />
                </div>
              )}
            </aside>
          </section>
        </div>
      </AppLayout>

      <ConfirmDialog entry={entryToDelete} onCancel={() => setEntryToDelete(null)} />
    </>
  );
}
