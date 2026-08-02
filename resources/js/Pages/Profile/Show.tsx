import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

type User = {
  name: string;
  email: string;
  role: string;
};

type ProfileProps = {
  user: User;
};

export default function Show({ user }: ProfileProps) {
  const { post, processing } = useForm();

  function logout() {
    post('/logout');
  }

  return (
    <>
      <Head title="Profile" />

      <AppLayout
        title="Profile"
        subtitle="Manage your account session and workspace identity."
        user={user}
      >
        <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
          <section className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-6 shadow-[0_18px_45px_rgba(23,32,27,0.08)]">
            <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex items-center gap-4">
                <div className="grid size-16 place-items-center rounded-lg bg-[#27615a] text-2xl font-black text-white">
                  {user.name
                    .split(' ')
                    .map((part) => part[0])
                    .join('')
                    .slice(0, 2)}
                </div>
                <div>
                  <p className="text-sm font-bold uppercase text-[#27615a]">Signed in as</p>
                  <h2 className="mt-1 text-2xl font-black text-[#17201b]">{user.name}</h2>
                  <p className="mt-1 text-sm text-[#59635d]">{user.email}</p>
                </div>
              </div>

              <button
                className="h-11 rounded-lg border border-[#a33b2f]/20 bg-[#a33b2f] px-5 text-sm font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-70"
                type="button"
                disabled={processing}
                onClick={logout}
              >
                {processing ? 'Logging out...' : 'Logout'}
              </button>
            </div>

            <div className="mt-8 grid gap-4 md:grid-cols-3">
              <div className="rounded-lg border border-[#17201b]/10 bg-[#f7faf4] p-4">
                <p className="text-xs font-bold uppercase text-[#7a5a3a]">Role</p>
                <p className="mt-2 text-sm font-bold text-[#17201b]">{user.role}</p>
              </div>
              <div className="rounded-lg border border-[#17201b]/10 bg-[#f7faf4] p-4">
                <p className="text-xs font-bold uppercase text-[#7a5a3a]">Status</p>
                <p className="mt-2 text-sm font-bold text-[#17201b]">Active session</p>
              </div>
              <div className="rounded-lg border border-[#17201b]/10 bg-[#f7faf4] p-4">
                <p className="text-xs font-bold uppercase text-[#7a5a3a]">Access</p>
                <p className="mt-2 text-sm font-bold text-[#17201b]">Core workspace</p>
              </div>
            </div>
          </section>

          <aside className="rounded-lg border border-[#17201b]/10 bg-[#fffffb] p-6 shadow-[0_18px_45px_rgba(23,32,27,0.08)]">
            <p className="text-sm font-extrabold uppercase text-[#27615a]">Next modules</p>
            <div className="mt-5 grid gap-3">
              {['Employee directory', 'Department structure', 'Leave approvals', 'Payroll overview'].map(
                (item) => (
                  <div
                    className="rounded-md border border-[#17201b]/10 bg-[#f7faf4] px-4 py-3 text-sm font-semibold text-[#34423a]"
                    key={item}
                  >
                    {item}
                  </div>
                ),
              )}
            </div>
          </aside>
        </div>
      </AppLayout>
    </>
  );
}
