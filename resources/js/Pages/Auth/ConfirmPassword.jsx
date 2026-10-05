import { Head, useForm } from '@inertiajs/react';
import { FormField, TextInput } from '../../Components/FormField';
import GuestLayout from '../../Layouts/GuestLayout';

export default function ConfirmPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    function submit(event) {
        event.preventDefault();
        post('/user/confirm-password');
    }

    return (
        <GuestLayout title="Confirm your password" subtitle="This is a sensitive area of the application. Please confirm your password before continuing.">
            <Head title="Confirm password" />

            {status && <div className="mb-4 rounded-lg bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">{status}</div>}

            <form onSubmit={submit} noValidate className="grid gap-5">
                <FormField label="Password" name="password" errors={errors} required>
                    <TextInput name="password" type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} autoComplete="current-password" errors={errors} required autoFocus />
                </FormField>

                <button type="submit" className="btn btn-primary w-full justify-center" disabled={processing}>
                    {processing ? 'Confirming…' : 'Confirm password'}
                </button>
            </form>
        </GuestLayout>
    );
}
