import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type LoginForm = {
  email: string;
  password: string;
  remember: boolean;
};

export default function Login() {
  const { data, setData, post, processing, errors } = useForm<LoginForm>({
    email: '',
    password: '',
    remember: false,
  });

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    post('/login');
  }

  return (
    <>
      <Head title="Login" />

      <main className="grid min-h-screen place-items-center bg-[#f4f7f1] bg-[linear-gradient(135deg,rgba(39,97,90,0.12),transparent_42%),linear-gradient(315deg,rgba(188,116,66,0.16),transparent_48%)] p-5 text-[#17201b] sm:p-8">
        <section
          className="grid min-h-0 w-full max-w-[920px] overflow-hidden rounded-lg border border-[#17201b]/10 bg-[#fffffb] shadow-[0_24px_70px_rgba(23,32,27,0.14)] md:min-h-[620px] md:grid-cols-[minmax(320px,440px)_minmax(280px,420px)]"
          aria-label="Login"
        >
          <div className="flex flex-col justify-center px-6 py-8 sm:px-14">
            <p className="mb-7 text-xs font-extrabold uppercase text-[#27615a]">Employeon</p>
            <h1 className="text-[2rem] font-bold leading-[1.05] text-[#17201b] sm:text-[2.4rem]">
              Welcome back
            </h1>
            <p className="mb-8 mt-4 text-base leading-7 text-[#59635d]">
              Sign in to manage your employee operations workspace.
            </p>

            <form className="grid gap-[18px]" onSubmit={submit}>
              <label className="grid gap-2 text-sm font-bold text-[#34423a]" htmlFor="email">
                Email
                <input
                  className="h-12 w-full rounded-lg border border-[#17201b]/15 bg-[#fbfcf8] px-3.5 font-[inherit] text-[#17201b] outline-none focus:border-[#27615a] focus:ring-3 focus:ring-[#27615a]/20"
                  id="email"
                  type="email"
                  value={data.email}
                  autoComplete="email"
                  required
                  onChange={(event) => setData('email', event.target.value)}
                />
              </label>
              {errors.email && <p className="-mt-2.5 text-sm text-[#a33b2f]">{errors.email}</p>}

              <label className="grid gap-2 text-sm font-bold text-[#34423a]" htmlFor="password">
                Password
                <input
                  className="h-12 w-full rounded-lg border border-[#17201b]/15 bg-[#fbfcf8] px-3.5 font-[inherit] text-[#17201b] outline-none focus:border-[#27615a] focus:ring-3 focus:ring-[#27615a]/20"
                  id="password"
                  type="password"
                  value={data.password}
                  autoComplete="current-password"
                  required
                  onChange={(event) => setData('password', event.target.value)}
                />
              </label>
              {errors.password && <p className="-mt-2.5 text-sm text-[#a33b2f]">{errors.password}</p>}

              <div className="flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                <label
                  className="flex items-center gap-2.5 font-semibold text-[#34423a]"
                  htmlFor="remember"
                >
                  <input
                    className="size-4 accent-[#27615a]"
                    id="remember"
                    type="checkbox"
                    checked={data.remember}
                    onChange={(event) => setData('remember', event.target.checked)}
                  />
                  Remember me
                </label>

                <a className="text-[#27615a] no-underline" href="/forgot-password">
                  Forgot password?
                </a>
              </div>

              <button
                className="h-[50px] cursor-pointer rounded-lg border-0 bg-[#27615a] font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-70"
                type="submit"
                disabled={processing}
              >
                {processing ? 'Signing in...' : 'Sign in'}
              </button>
            </form>
          </div>

          <aside
            className="order-first flex min-h-45 items-end bg-[linear-gradient(145deg,rgba(23,32,27,0.14),rgba(23,32,27,0.72)),url('https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center p-10 text-white md:order-none"
            aria-hidden="true"
          >
            <div className="grid gap-1.5">
              <span className="text-sm font-extrabold uppercase">People</span>
              <strong className="max-w-[8ch] text-5xl leading-[0.95]">Operations</strong>
            </div>
          </aside>
        </section>
      </main>
    </>
  );
}
