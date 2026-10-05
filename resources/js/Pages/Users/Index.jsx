import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Icon from '../../Components/Icons';
import { FieldError, FormField, SelectInput, TextInput } from '../../Components/FormField';
import Modal, { ConfirmDialog } from '../../Components/Modal';
import Pagination from '../../Components/Pagination';
import { Badge, Button, Card, PageHeader, SearchField, TableState } from '../../Components/UI';
import { asArray, firstDefined, formatDate, normalizePagination, relationName, titleCase } from '../../Utils';
import { paths, resourcePath } from '../../Utils/routes';

const fallbackRoles = [{ id: 1, name: 'admin' }, { id: 2, name: 'employee' }];

export default function UsersIndex({ users, roles = [], errors: pageErrors = {} }) {
    const page = usePage();
    const rows = asArray(users);
    const meta = normalizePagination(users);
    const availableRoles = asArray(roles).length ? asArray(roles) : fallbackRoles;
    const [search, setSearch] = useState('');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const currentUserId = firstDefined(page.props.auth?.user?.id, page.props.user?.id);
    const form = useForm({ full_name: '', username: '', password: '', role_id: '' });
    const deleteForm = useForm({});
    const isEditing = Boolean(editingUser?.id);
    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return rows;
        return rows.filter((user) => `${relationName(user, ['name', 'full_name'])} ${user.username || ''} ${roleName(user)}`.toLowerCase().includes(query));
    }, [rows, search]);

    function roleName(user) {
        return titleCase(firstDefined(user.role?.name, user.role_name, typeof user.role === 'string' ? user.role : 'team member'));
    }

    function openCreate() {
        setEditingUser(null);
        form.setData({ full_name: '', username: '', password: '', role_id: String(firstDefined(availableRoles[0]?.id, '1')) });
        form.clearErrors();
        setModalOpen(true);
    }

    function openEdit(user) {
        setEditingUser(user);
        form.clearErrors();
        form.setData({
            full_name: firstDefined(user.full_name, user.name, ''),
            username: firstDefined(user.username, ''),
            password: '',
            role_id: String(firstDefined(user.role_id, user.role?.id, user.role_name, typeof user.role === 'string' ? user.role : availableRoles[0]?.id, '1')),
        });
        setModalOpen(true);
    }

    function closeModal() {
        if (form.processing) return;
        setModalOpen(false);
        setEditingUser(null);
        form.clearErrors();
    }

    function submit(event) {
        event.preventDefault();
        form.transform((data) => ({ ...data, password: data.password || null }));
        const options = { preserveScroll: true, onSuccess: () => { setModalOpen(false); setEditingUser(null); } };
        if (isEditing) form.put(resourcePath('users', editingUser.id), options);
        else form.post(paths.users, options);
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        deleteForm.delete(resourcePath('users', deleteTarget.id), { preserveScroll: true, onSuccess: () => setDeleteTarget(null) });
    }

    return (
        <>
            <Head title="Users · InvControl" />
            <PageHeader
                eyebrow="Administration"
                title="Users"
                description="Manage team identities, usernames, and role-based access. Only administrators can make changes."
                actions={<Button type="button" onClick={openCreate}><Icon name="plus" size={18} />Add user</Button>}
            />

            <Card className="overflow-hidden">
                <div className="border-b border-slate-200 bg-slate-50 p-4 no-print">
                    <SearchField label="Find a user" value={search} onChange={setSearch} placeholder="Search name, username, or role" className="max-w-md" />
                </div>
                <div className="table-shell">
                    <table className="data-table">
                        <caption className="sr-only">Application users, usernames, and roles</caption>
                        <thead><tr><th>User</th><th>Username</th><th>Role</th><th>Joined</th><th className="text-right">Actions</th></tr></thead>
                        <tbody>
                            {filtered.length ? filtered.map((user) => {
                                return (
                                    <tr key={user.id}>
                                        <td>
                                            <div className="flex items-center gap-3">
                                                <span className="grid size-8 shrink-0 place-items-center rounded-full bg-teal-100 text-xs font-bold text-teal-900" aria-hidden="true">{relationName(user, ['name', 'full_name'], 'U').charAt(0).toUpperCase()}</span>
                                                <span className="font-semibold text-slate-900">{relationName(user, ['name', 'full_name'], 'Unnamed user')}{Number(user.id) === Number(currentUserId) && <span className="ml-2 text-xs font-medium text-teal-700">You</span>}</span>
                                            </div>
                                        </td>
                                        <td><span className="font-mono text-xs text-slate-600">{user.username || '—'}</span></td>
                                        <td><Badge tone={roleName(user).toLowerCase() === 'admin' ? 'teal' : 'slate'}>{roleName(user)}</Badge></td>
                                        <td className="whitespace-nowrap text-xs text-slate-500">{formatDate(firstDefined(user.created_at, user.joined_at))}</td>
                                        <td>
                                            <div className="flex justify-end gap-1 no-print">
                                                <button type="button" className="icon-button" onClick={() => openEdit(user)} aria-label={`Edit ${relationName(user, ['name', 'full_name'], 'user')}`} title="Edit user"><Icon name="edit" size={18} /></button>
                                                <button
                                                    type="button"
                                                    className="icon-button hover:!bg-red-50 hover:!text-red-700 disabled:opacity-40"
                                                    onClick={() => setDeleteTarget(user)}
                                                    disabled={Number(user.id) === Number(currentUserId)}
                                                    aria-label={`Delete ${relationName(user, ['name', 'full_name'], 'user')}`}
                                                    title={Number(user.id) === Number(currentUserId) ? 'You cannot delete your own account' : 'Delete user'}
                                                >
                                                    <Icon name="trash" size={18} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : <TableState colSpan="5" empty error={String(pageErrors.users || '')} emptyTitle={search ? 'No matching users' : 'No users yet'} emptyDescription={search ? 'Try a different name, username, or role.' : 'Add the first team member to this workspace.'} emptyIcon="users" />}
                        </tbody>
                    </table>
                </div>
                <Pagination meta={meta} currentQuery={{ search }} />
            </Card>

            <Modal
                open={modalOpen}
                onClose={closeModal}
                title={isEditing ? 'Edit user' : 'Add user'}
                description={isEditing ? 'Update profile details, role, and account access.' : 'Create an account and assign its starting role.'}
                footer={(
                    <>
                        <button type="button" className="btn btn-secondary" onClick={closeModal} disabled={form.processing}>Cancel</button>
                        <Button type="submit" form="user-form" busy={form.processing}>{isEditing ? 'Save changes' : 'Create user'}</Button>
                    </>
                )}
            >
                <form id="user-form" onSubmit={submit} className="grid gap-5 sm:grid-cols-2" noValidate>
                    <FormField label="Full name" name="full_name" errors={form.errors} required>
                        <TextInput name="full_name" value={form.data.full_name} onChange={(event) => form.setData('full_name', event.target.value)} autoComplete="name" errors={form.errors} required />
                    </FormField>
                    <FormField label="Username" name="username" errors={form.errors} required>
                        <TextInput name="username" value={form.data.username} onChange={(event) => form.setData('username', event.target.value)} autoComplete="username" autoCapitalize="none" errors={form.errors} required />
                    </FormField>
                    <FormField label="Role" name="role_id" errors={form.errors} required>
                        <SelectInput name="role_id" value={form.data.role_id} onChange={(event) => form.setData('role_id', event.target.value)} errors={form.errors} required>
                            <option value="">Select a role</option>
                            {availableRoles.map((role) => <option key={role.id || role.role_name || role.name} value={role.id || role.name}>{titleCase(role.role_name || role.name || role.label || role.id)}</option>)}
                        </SelectInput>
                    </FormField>
                    <FormField label={isEditing ? 'New password' : 'Password'} name="password" errors={form.errors} hint={isEditing ? 'Leave blank to keep the current password.' : 'Use at least 6 characters.'} required={!isEditing}>
                        <TextInput name="password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="new-password" errors={form.errors} required={!isEditing} />
                    </FormField>
                    {form.errors.general && <div className="sm:col-span-2"><FieldError errors={form.errors} name="general" /></div>}
                </form>
            </Modal>

            <ConfirmDialog
                open={Boolean(deleteTarget)}
                onClose={() => !deleteForm.processing && setDeleteTarget(null)}
                onConfirm={confirmDelete}
                busy={deleteForm.processing}
                title="Delete user account?"
                confirmLabel="Delete user"
                message={`Delete ${relationName(deleteTarget, ['name', 'full_name'], 'this user')}? Their historical activity will remain, but the account will no longer be able to sign in.`}
            />
        </>
    );
}
