import { Head, Link, useForm } from '@inertiajs/react';
import { FormField, TextInput } from '../../Components/FormField';
import GuestLayout from '../../Layouts/GuestLayout';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    function submit(event) {
        event.preventDefault();
        post('/forgot-password');
    }

    return (
        <GuestLayout
            title="Forgot your password?"
            subtitle="Enter your account email and we will send a reset link."
            footer={(
                <>
                    Remembered it?{' '}
                    <Link href="/login" className="font-semibold text-teal-700 hover:text-teal-900">Back to sign in</Link>
                </>
            )}
        >
            <Head title="Forgot password" />

            {status && <div className="mb-4 rounded-lg bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">{status}</div>}

            <form onSubmit={submit} noValidate className="grid gap-5">
                <FormField label="Email" name="email" errors={errors} required>
                    <TextInput name="email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} autoComplete="email" autoCapitalize="none" errors={errors} required autoFocus />
                </FormField>

                <button type="submit" className="btn btn-primary w-full justify-center" disabled={processing}>
                    {processing ? 'Sending…' : 'Email password reset link'}
                </button>
            </form>
        </GuestLayout>
    );
}
