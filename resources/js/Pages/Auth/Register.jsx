import { Head, Link, useForm } from '@inertiajs/react';
import { FormField, TextInput } from '../../Components/FormField';
import GuestLayout from '../../Layouts/GuestLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        post('/register');
    }

    return (
        <GuestLayout
            title="Create your account"
            subtitle="New accounts start with viewing access. An administrator can grant more."
            footer={(
                <>
                    Already registered?{' '}
                    <Link href="/login" className="font-semibold text-teal-700 hover:text-teal-900">Sign in</Link>
                </>
            )}
        >
            <Head title="Create an account" />

            <form onSubmit={submit} noValidate className="grid gap-5">
                <FormField label="Full name" name="full_name" errors={errors} required>
                    <TextInput name="full_name" value={data.full_name} onChange={(event) => setData('full_name', event.target.value)} autoComplete="name" errors={errors} required autoFocus />
                </FormField>

                <FormField label="Username" name="username" errors={errors} required>
                    <TextInput name="username" value={data.username} onChange={(event) => setData('username', event.target.value)} autoComplete="username" autoCapitalize="none" errors={errors} required />
                </FormField>

                <FormField label="Email" name="email" errors={errors} required>
                    <TextInput name="email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} autoComplete="email" autoCapitalize="none" errors={errors} required />
                </FormField>

                <FormField label="Password" name="password" errors={errors} hint="Use at least 8 characters." required>
                    <TextInput name="password" type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} autoComplete="new-password" errors={errors} required />
                </FormField>

                <FormField label="Confirm password" name="password_confirmation" errors={errors} required>
                    <TextInput name="password_confirmation" type="password" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} autoComplete="new-password" errors={errors} required />
                </FormField>

                <button type="submit" className="btn btn-primary w-full justify-center" disabled={processing}>
                    {processing ? 'Creating account…' : 'Create account'}
                </button>
            </form>
        </GuestLayout>
    );
}
