import { Head, useForm } from '@inertiajs/react';
import { FieldError, FormField, TextInput } from '../../Components/FormField';
import GuestLayout from '../../Layouts/GuestLayout';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors } = useForm({
        token: token || '',
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        post('/reset-password');
    }

    return (
        <GuestLayout title="Choose a new password" subtitle="Your reset link was verified. Pick a password you have not used before.">
            <Head title="Reset password" />

            <form onSubmit={submit} noValidate className="grid gap-5">
                <FormField label="Email" name="email" errors={errors} required>
                    <TextInput name="email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} autoComplete="email" autoCapitalize="none" errors={errors} required />
                </FormField>

                <FormField label="New password" name="password" errors={errors} hint="Use at least 8 characters." required>
                    <TextInput name="password" type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} autoComplete="new-password" errors={errors} required autoFocus />
                </FormField>

                <FormField label="Confirm password" name="password_confirmation" errors={errors} required>
                    <TextInput name="password_confirmation" type="password" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} autoComplete="new-password" errors={errors} required />
                </FormField>

                <FieldError errors={errors} name="token" />

                <button type="submit" className="btn btn-primary w-full justify-center" disabled={processing}>
                    {processing ? 'Resetting…' : 'Reset password'}
                </button>
            </form>
        </GuestLayout>
    );
}
