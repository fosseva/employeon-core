import { Head } from '@inertiajs/react';
import { CalendarClock, ClipboardList, Construction } from 'lucide-react';
import AppLayout from '../Layouts/AppLayout';

type User = {
  name: string;
  email: string;
  role: string;
};

type Template = {
  title: string;
  subtitle: string;
  stats?: Array<{ label: string; value: string }>;
  rows?: Array<{ name: string; status: string; due: string }>;
};

type ModuleTemplateProps = {
  user: User;
  template: Template;
};

export default function ModuleTemplate({ user, template }: ModuleTemplateProps) {
  return (
    <>
      <Head title={template.title} />

      <AppLayout title={template.title} subtitle={template.subtitle} user={user}>
        <section className="grid min-h-[calc(100vh-7rem)] place-items-center rounded-lg border border-dashed border-[#17201b]/15 bg-[#fffffb] px-5 py-12">
          <div className="max-w-xl text-center">
            <div className="mx-auto grid size-12 place-items-center rounded-lg bg-[#eef7f3] text-[#27615a]">
              <Construction className="size-6" />
            </div>
            <p className="mt-5 text-xs font-black uppercase text-[#27615a]">Coming soon</p>
            <h2 className="mt-2 text-3xl font-black text-[#17201b]">{template.title}</h2>
            <p className="mt-3 text-sm leading-6 text-[#59635d]">
              This module is registered in the Employeon shell and will get its workflow,
              permissions, filters, and records when the feature is built.
            </p>
            <div className="mt-6 grid gap-3 text-left sm:grid-cols-2">
              <div className="rounded-lg border border-[#17201b]/10 bg-[#f7faf4] p-4">
                <ClipboardList className="size-5 text-[#27615a]" />
                <p className="mt-3 text-sm font-black text-[#17201b]">Workflow shell ready</p>
                <p className="mt-1 text-xs leading-5 text-[#59635d]">Navigation and routing are already wired.</p>
              </div>
              <div className="rounded-lg border border-[#17201b]/10 bg-[#f7faf4] p-4">
                <CalendarClock className="size-5 text-[#27615a]" />
                <p className="mt-3 text-sm font-black text-[#17201b]">Implementation pending</p>
                <p className="mt-1 text-xs leading-5 text-[#59635d]">Add the module package when the domain is ready.</p>
              </div>
            </div>
          </div>
        </section>
      </AppLayout>
    </>
  );
}
