import { Head, useForm, usePage } from '@inertiajs/react';
import { FormField, TextInput } from '../Components/FormField';
import { Button, Card, PageHeader } from '../Components/UI';

/**
 * Authenticated account page: profile (incl. the email-update path used for
 * password recovery) and password changes, both handled by Fortify.
 */
export default function Account() {
    const page = usePage();
    const user = page.props.auth?.user || {};
    const status = page.props.status || page.props.flash?.message;

    const profileForm = useForm({
        full_name: user.full_name || '',
        username: user.username || '',
        email: user.email || '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function submitProfile(event) {
        event.preventDefault();
        profileForm.put('/user/profile-information', {
            errorBag: 'updateProfileInformation',
            preserveScroll: true,
        });
    }

    function submitPassword(event) {
        event.preventDefault();
        passwordForm.put('/user/password', {
            errorBag: 'updatePassword',
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    }

    return (
        <>
            <Head title="My account · InvControl" />
            <PageHeader
                eyebrow="Account"
                title="My account"
                description="Update your profile details and password. Your email is used for password recovery."
            />

            {status && <div className="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">{status}</div>}

            <div className="grid gap-6 lg:grid-cols-2">
                <Card className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">Profile</h2>
                    <p className="mt-1 text-sm text-slate-500">Signed in as <span className="font-mono">{user.username}</span>.</p>

                    <form onSubmit={submitProfile} noValidate className="mt-6 grid gap-5">
                        <FormField label="Full name" name="full_name" errors={profileForm.errors} required>
                            <TextInput name="full_name" value={profileForm.data.full_name} onChange={(event) => profileForm.setData('full_name', event.target.value)} autoComplete="name" errors={profileForm.errors} required />
                        </FormField>

                        <FormField label="Username" name="username" errors={profileForm.errors} required>
                            <TextInput name="username" value={profileForm.data.username} onChange={(event) => profileForm.setData('username', event.target.value)} autoComplete="username" autoCapitalize="none" errors={profileForm.errors} required />
                        </FormField>

                        <FormField label="Email" name="email" errors={profileForm.errors} hint="Used to recover your password." required>
                            <TextInput name="email" type="email" value={profileForm.data.email} onChange={(event) => profileForm.setData('email', event.target.value)} autoComplete="email" autoCapitalize="none" errors={profileForm.errors} required />
                        </FormField>

                        <div>
                            <Button type="submit" busy={profileForm.processing}>Save profile</Button>
                        </div>
                    </form>
                </Card>

                <Card className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">Password</h2>
                    <p className="mt-1 text-sm text-slate-500">Use at least 8 characters for a new password.</p>

                    <form onSubmit={submitPassword} noValidate className="mt-6 grid gap-5">
                        <FormField label="Current password" name="current_password" errors={passwordForm.errors} required>
                            <TextInput name="current_password" type="password" value={passwordForm.data.current_password} onChange={(event) => passwordForm.setData('current_password', event.target.value)} autoComplete="current-password" errors={passwordForm.errors} required />
                        </FormField>

                        <FormField label="New password" name="password" errors={passwordForm.errors} required>
                            <TextInput name="password" type="password" value={passwordForm.data.password} onChange={(event) => passwordForm.setData('password', event.target.value)} autoComplete="new-password" errors={passwordForm.errors} required />
                        </FormField>

                        <FormField label="Confirm new password" name="password_confirmation" errors={passwordForm.errors} required>
                            <TextInput name="password_confirmation" type="password" value={passwordForm.data.password_confirmation} onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)} autoComplete="new-password" errors={passwordForm.errors} required />
                        </FormField>

                        <div>
                            <Button type="submit" busy={passwordForm.processing}>Update password</Button>
                        </div>
                    </form>
                </Card>
            </div>
        </>
    );
}
