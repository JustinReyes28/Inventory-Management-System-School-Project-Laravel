import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Icon from '../../Components/Icons';
import { FieldError, FormField, TextInput } from '../../Components/FormField';
import Modal, { ConfirmDialog } from '../../Components/Modal';
import Pagination from '../../Components/Pagination';
import { Button, Card, EmptyState, PageHeader, SearchField, TableState } from '../../Components/UI';
import { asArray, firstDefined, formatNumber, normalizePagination, relationName } from '../../Utils';
import { paths, resourcePath } from '../../Utils/routes';

const blankCategory = { category_name: '' };

export default function CategoriesIndex({ categories, errors: pageErrors = {} }) {
    const rows = asArray(categories);
    const meta = normalizePagination(categories);
    const [search, setSearch] = useState('');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const form = useForm({ ...blankCategory });
    const deleteForm = useForm({});
    const isEditing = Boolean(editingCategory?.id);
    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return rows;
        return rows.filter((category) => relationName(category, ['name', 'category_name']).toLowerCase().includes(query));
    }, [rows, search]);

    function openCreate() {
        setEditingCategory(null);
        form.setData({ ...blankCategory });
        form.clearErrors();
        setModalOpen(true);
    }

    function openEdit(category) {
        setEditingCategory(category);
        form.clearErrors();
        form.setData({
            category_name: firstDefined(category.category_name, category.name, ''),
        });
        setModalOpen(true);
    }

    function closeModal() {
        if (form.processing) return;
        setModalOpen(false);
        setEditingCategory(null);
        form.clearErrors();
    }

    function submit(event) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setModalOpen(false);
                setEditingCategory(null);
            },
        };
        if (isEditing) form.put(resourcePath('categories', editingCategory.id), options);
        else form.post(paths.categories, options);
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        deleteForm.delete(resourcePath('categories', deleteTarget.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    }

    return (
        <>
            <Head title="Categories · InvControl" />
            <PageHeader
                eyebrow="Catalog structure"
                title="Categories"
                description="Keep products organized into clear, useful groups."
                actions={<Button type="button" onClick={openCreate}><Icon name="plus" size={18} />Add category</Button>}
            />

            <Card className="overflow-hidden">
                <div className="border-b border-slate-200 bg-slate-50 p-4 no-print">
                    <SearchField label="Find a category" value={search} onChange={setSearch} placeholder="Search by category name" className="max-w-md" />
                </div>
                <div className="table-shell">
                    <table className="data-table">
                        <caption className="sr-only">Inventory categories and item counts</caption>
                        <thead><tr><th>ID</th><th>Name</th><th className="text-right">Active items</th><th className="text-right">Actions</th></tr></thead>
                        <tbody>
                            {filtered.length ? filtered.map((category) => (
                                <tr key={category.id}>
                                    <td><span className="font-mono text-xs text-slate-500">#{category.id}</span></td>
                                    <td><span className="font-semibold text-slate-900">{relationName(category, ['name', 'category_name'], 'Unnamed category')}</span></td>
                                    <td className="text-right"><span className="font-mono text-xs font-semibold text-slate-700">{formatNumber(firstDefined(category.product_count, category.products_count, category.items_count, 0))}</span></td>
                                    <td>
                                        <div className="flex justify-end gap-1 no-print">
                                            <button type="button" className="icon-button" onClick={() => openEdit(category)} aria-label={`Edit ${relationName(category, ['name', 'category_name'], 'category')}`} title="Edit category"><Icon name="edit" size={18} /></button>
                                            <button type="button" className="icon-button hover:!bg-red-50 hover:!text-red-700" onClick={() => setDeleteTarget(category)} aria-label={`Delete ${relationName(category, ['name', 'category_name'], 'category')}`} title="Delete category"><Icon name="trash" size={18} /></button>
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <TableState
                                    colSpan="4"
                                    empty
                                    error={String(pageErrors.categories || '')}
                                    emptyTitle={search ? 'No matching categories' : 'No categories yet'}
                                    emptyDescription={search ? 'Try another category name.' : 'Add a category before creating inventory items.'}
                                    emptyIcon="layers"
                                />
                            )}
                        </tbody>
                    </table>
                </div>
                <Pagination meta={meta} currentQuery={{ search }} />
            </Card>

            <Modal
                open={modalOpen}
                onClose={closeModal}
                title={isEditing ? 'Edit category' : 'Add category'}
                description="Use a short, recognizable name that helps staff find items quickly."
                size="sm"
                footer={(
                    <>
                        <button type="button" className="btn btn-secondary" onClick={closeModal} disabled={form.processing}>Cancel</button>
                        <Button type="submit" form="category-form" busy={form.processing}>{isEditing ? 'Save changes' : 'Create category'}</Button>
                    </>
                )}
            >
                <form id="category-form" onSubmit={submit} className="space-y-5" noValidate>
                    <FormField label="Category name" name="category_name" errors={form.errors} required>
                        <TextInput
                            name="category_name"
                            value={form.data.category_name}
                            onChange={(event) => form.setData('category_name', event.target.value)}
                            placeholder="Medical supplies"
                            errors={form.errors}
                            required
                        />
                    </FormField>
                    {form.errors.general && <FieldError errors={form.errors} name="general" />}
                </form>
            </Modal>

            <ConfirmDialog
                open={Boolean(deleteTarget)}
                onClose={() => !deleteForm.processing && setDeleteTarget(null)}
                onConfirm={confirmDelete}
                busy={deleteForm.processing}
                title="Delete category?"
                confirmLabel="Delete category"
                message={`Delete “${relationName(deleteTarget, ['name', 'category_name'], 'this category')}”? Categories with assigned items may need to be reassigned first.`}
            />
        </>
    );
}
