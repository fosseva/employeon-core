import { Head } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

type User = {
  name: string;
  email: string;
  role: string;
};

type Template = {
  title: string;
  subtitle: string;
};

type ModuleTemplateProps = {
  user: User;
  template: Template;
};

const stats = [
  ['Open items', '24'],
  ['Pending review', '8'],
  ['Completed', '143'],
];

const rows = [
  ['Sample record', 'Draft', 'Today'],
  ['Onboarding task', 'In review', 'Tomorrow'],
  ['Policy update', 'Ready', 'This week'],
  ['Monthly summary', 'Queued', 'Next week'],
];

export default function ModuleTemplate({ user, template }: ModuleTemplateProps) {
  return (
    <>
      <Head title={template.title} />

      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <div className="grid gap-5">
          <section className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-5 shadow-[0_18px_45px_rgba(23,32,27,0.08)]">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-xs font-black uppercase text-[#27615a]">Dummy template</p>
                <h2 className="mt-1 text-2xl font-black text-[#17201b]">{template.title}</h2>
                <p className="mt-1 max-w-2xl text-sm leading-6 text-[#59635d]">
                  This placeholder gives the module a realistic surface while the actual data model,
                  permissions, filters, and workflows are added.
                </p>
              </div>
              <button
                className="h-10 rounded-lg bg-[#27615a] px-4 text-sm font-extrabold text-white"
                type="button"
              >
                New item
              </button>
            </div>
          </section>

          <section className="grid gap-4 md:grid-cols-3">
            {stats.map(([label, value]) => (
              <div
                className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-4 shadow-[0_18px_45px_rgba(23,32,27,0.06)]"
                key={label}
              >
                <p className="text-xs font-bold uppercase text-[#7a5a3a]">{label}</p>
                <p className="mt-2 text-3xl font-black text-[#17201b]">{value}</p>
              </div>
            ))}
          </section>

          <section className="overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] shadow-[0_18px_45px_rgba(23,32,27,0.08)]">
            <div className="border-b border-[#17201b]/10 px-5 py-4">
              <p className="text-sm font-black text-[#17201b]">Recent activity</p>
              <p className="mt-1 text-xs text-[#59635d]">A small dummy table for layout testing.</p>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[620px] text-left text-sm">
                <thead className="bg-[#f7faf4] text-xs font-black uppercase text-[#7a5a3a]">
                  <tr>
                    <th className="px-5 py-3">Name</th>
                    <th className="px-5 py-3">Status</th>
                    <th className="px-5 py-3">Due</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#17201b]/10">
                  {rows.map(([name, status, due]) => (
                    <tr key={name}>
                      <td className="px-5 py-3 font-bold text-[#17201b]">{name}</td>
                      <td className="px-5 py-3 text-[#59635d]">{status}</td>
                      <td className="px-5 py-3 text-[#59635d]">{due}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </AppLayout>
    </>
  );
}
