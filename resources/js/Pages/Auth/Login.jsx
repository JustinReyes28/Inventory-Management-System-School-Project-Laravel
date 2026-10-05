import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '../../Components/Icons';
import { FormField, TextInput } from '../../Components/FormField';
import { Button, Notice } from '../../Components/UI';
import { getErrors } from '../../Utils';
import { paths } from '../../Utils/routes';

export default function Login({ errors = {}, old = {} }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing } = useForm({
        username: old.username || '',
        password: old.password || '',
        remember: Boolean(old.remember),
    });
    const credentialError = errors.username || errors.email;

    function submit(event) {
        event.preventDefault();
        post(paths.login, { onFinish: () => setData('password', '') });
    }

    return (
        <>
            <Head title="Sign in · InvControl" />
            <main className="grid min-h-dvh bg-white lg:grid-cols-[minmax(24rem,0.9fr)_minmax(32rem,1.1fr)]">
                <section className="login-grid relative hidden overflow-hidden bg-slate-950 px-12 py-10 text-white lg:flex lg:flex-col" aria-label="InvControl inventory operations">
                    <div className="absolute -right-24 top-24 size-72 rounded-full bg-teal-600/15 blur-3xl" aria-hidden="true" />
                    <div className="relative flex items-center gap-3">
                        <span className="grid size-11 place-items-center rounded-xl bg-teal-700 shadow-lg shadow-teal-950">
                            <Icon name="layers" size={24} />
                        </span>
                        <div>
                            <p className="text-lg font-bold tracking-tight">InvControl</p>
                            <p className="font-mono text-[10px] uppercase tracking-[0.18em] text-teal-300">Stock operations</p>
                        </div>
                    </div>

                    <div className="relative my-auto max-w-xl py-16">
                        <p className="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-teal-300">A calmer stockroom</p>
                        <h1 className="mt-4 text-4xl font-semibold leading-tight tracking-[-0.045em] xl:text-5xl">
                            Know what needs attention before it becomes urgent.
                        </h1>
                        <p className="mt-5 max-w-lg text-base leading-7 text-slate-300">
                            Track inventory, batches, expiry risk, and every stock movement from one dependable workspace.
                        </p>

                        <div className="mt-10 grid grid-cols-3 gap-3" aria-hidden="true">
                            {[
                                ['Live stock', '01'],
                                ['Expiry watch', '07'],
                                ['Full audit', '24'],
                            ].map(([label, value]) => (
                                <div key={label} className="rounded-xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                                    <p className="font-mono text-2xl font-semibold text-teal-200">{value}</p>
                                    <p className="mt-1 text-xs text-slate-400">{label}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="relative flex items-center gap-2 border-t border-white/10 pt-5 text-xs text-slate-400">
                        <Icon name="lock" size={15} />
                        <span>Secure, role-aware inventory management</span>
                    </div>
                </section>

                <section className="flex min-h-dvh items-center justify-center bg-slate-50 px-5 py-10 sm:px-8">
                    <div className="w-full max-w-md">
                        <div className="mb-8 flex items-center gap-3 lg:hidden">
                            <span className="grid size-11 place-items-center rounded-xl bg-teal-700 text-white"><Icon name="layers" size={24} /></span>
                            <div>
                                <p className="text-lg font-bold tracking-tight text-slate-950">InvControl</p>
                                <p className="font-mono text-[10px] uppercase tracking-[0.18em] text-teal-700">Stock operations</p>
                            </div>
                        </div>

                        <div className="app-card p-6 sm:p-8">
                            <p className="eyebrow">Secure workspace</p>
                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-950">Welcome back</h1>
                            <p className="mt-2 text-sm leading-6 text-slate-600">Sign in to review today’s inventory signals and stock activity.</p>

                            {(errors.general || errors.message) && (
                                <Notice tone="error" className="mt-6" title="Sign in failed">{errors.general || errors.message}</Notice>
                            )}

                            <form className="mt-7 space-y-5" onSubmit={submit} noValidate>
                                <FormField label="Username" name="username" errors={{ ...errors, username: credentialError }} required>
                                    <TextInput
                                        name="username"
                                        type="text"
                                        autoComplete="username"
                                        autoCapitalize="none"
                                        autoFocus
                                        value={data.username}
                                        onChange={(event) => setData('username', event.target.value)}
                                        placeholder="Enter your username"
                                        errors={{ ...errors, username: credentialError }}
                                        required
                                    />
                                </FormField>

                                <FormField label="Password" name="password" errors={errors} required>
                                    <div className="relative">
                                        <TextInput
                                            name="password"
                                            type={showPassword ? 'text' : 'password'}
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(event) => setData('password', event.target.value)}
                                            placeholder="Enter your password"
                                            errors={errors}
                                            className="pr-12"
                                            required
                                        />
                                        <button
                                            type="button"
                                            className="absolute right-1 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                                            onClick={() => setShowPassword((visible) => !visible)}
                                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                                            aria-pressed={showPassword}
                                        >
                                            <Icon name="eye" size={18} />
                                        </button>
                                    </div>
                                </FormField>

                                <label className="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-medium text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        className="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                        checked={data.remember}
                                        onChange={(event) => setData('remember', event.target.checked)}
                                    />
                                    Keep me signed in on this device
                                </label>

                                <Button type="submit" className="w-full" busy={processing} disabled={processing || !data.username || !data.password}>
                                    {!processing && <Icon name="logout" size={18} className="rotate-180" />}
                                    {processing ? 'Signing in…' : 'Sign in to InvControl'}
                                </Button>
                            </form>
                        </div>

                        <p className="mt-6 text-center text-xs leading-5 text-slate-500">
                            Need access? Ask an administrator to create your account.
                        </p>
                        {getErrors(errors, 'username') && <p className="sr-only">Check the highlighted username and password fields.</p>}
                    </div>
                </section>
            </main>
        </>
    );
}
