import { Head, Link, useForm } from '@inertiajs/react';
import { FormField, TextInput } from '../../Components/FormField';
import GuestLayout from '../../Layouts/GuestLayout';

export default function Login({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    function submit(event) {
        event.preventDefault();
        post('/login');
    }

    return (
        <GuestLayout
            title="Sign in"
            subtitle="Use your username and password to reach the inventory workspace."
            footer={(
                <>
                    New here?{' '}
                    <Link href="/register" className="font-semibold text-teal-700 hover:text-teal-900">Create an account</Link>
                    <span className="mx-2 text-slate-300">·</span>
                    <Link href="/forgot-password" className="font-semibold text-teal-700 hover:text-teal-900">Forgot your password?</Link>
                </>
            )}
        >
            <Head title="Sign in" />

            {status && <div className="mb-4 rounded-lg bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">{status}</div>}

            <form onSubmit={submit} noValidate className="grid gap-5">
                <FormField label="Username" name="username" errors={errors} required>
                    <TextInput
                        name="username"
                        value={data.username}
                        onChange={(event) => setData('username', event.target.value)}
                        autoComplete="username"
                        autoCapitalize="none"
                        errors={errors}
                        required
                        autoFocus
                    />
                </FormField>

                <FormField label="Password" name="password" errors={errors} required>
                    <TextInput
                        name="password"
                        type="password"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        autoComplete="current-password"
                        errors={errors}
                        required
                    />
                </FormField>

                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        className="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                        checked={data.remember}
                        onChange={(event) => setData('remember', event.target.checked)}
                    />
                    Remember me
                </label>

                <button type="submit" className="btn btn-primary w-full justify-center" disabled={processing}>
                    {processing ? 'Signing in…' : 'Sign in'}
                </button>
            </form>
        </GuestLayout>
    );
}
