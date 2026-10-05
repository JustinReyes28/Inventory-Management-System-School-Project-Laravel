import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Icon from '../../Components/Icons';
import { FieldError, FormField, SelectInput, TextInput } from '../../Components/FormField';
import Modal, { ConfirmDialog } from '../../Components/Modal';
import Pagination from '../../Components/Pagination';
import useInertiaLoading from '../../Hooks/useInertiaLoading';
import { Badge, Button, Card, EmptyState, FilterBar, PageHeader, SearchField, StockBadge, TableState } from '../../Components/UI';
import { asArray, firstDefined, formatCurrency, formatNumber, normalizePagination, numberValue, relationName } from '../../Utils';
import { paths, resourcePath } from '../../Utils/routes';

const blankItem = { sku: '', name: '', category_id: '', price: '', quantity: '', low_stock_threshold: 10 };

export default function ItemsIndex({ items, categories = [], filters = {}, errors: pageErrors = {} }) {
    const loading = useInertiaLoading();
    const urlFilters = new URLSearchParams(window.location.search);
    const initialSearch = firstDefined(filters.search, urlFilters.get('search'), '');
    const initialCategory = String(firstDefined(filters.category_id, filters.categoryId, urlFilters.get('category_id'), ''));
    const initialLowStock = Boolean(firstDefined(filters.low_stock, filters.lowStock, urlFilters.get('low_stock'), false));
    const [search, setSearch] = useState(initialSearch);
    const [categoryId, setCategoryId] = useState(initialCategory);
    const [lowStock, setLowStock] = useState(initialLowStock);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState(null);
    const [archiveTarget, setArchiveTarget] = useState(null);
    const lastRequest = useRef(JSON.stringify({ search: initialSearch, category_id: initialCategory, low_stock: initialLowStock ? '1' : '' }));
    const rows = asArray(items);
    const meta = normalizePagination(items, Number(filters.page) || 1);

    const form = useForm({ ...blankItem });
    const archiveForm = useForm({});
    const isEditing = Boolean(editingItem?.id);


    useEffect(() => {
        const timer = window.setTimeout(() => {
            const next = {
                search: search.trim(),
                category_id: categoryId,
                low_stock: lowStock ? '1' : '',
                page: '',
            };
            const signature = JSON.stringify(next);
            if (signature === lastRequest.current) return;
            lastRequest.current = signature;
            router.get(paths.items, next, { preserveState: true, preserveScroll: true, replace: true });
        }, search ? 350 : 0);

        return () => window.clearTimeout(timer);
    }, [search, categoryId, lowStock]);

    function resetFilters() {
        setSearch('');
        setCategoryId('');
        setLowStock(false);
    }

    function openCreate() {
        setEditingItem(null);
        form.setData({ ...blankItem });
        form.clearErrors();
        setModalOpen(true);
    }

    function openEdit(item) {
        setEditingItem(item);
        form.clearErrors();
        form.setData({
            sku: firstDefined(item.sku, ''),
            name: firstDefined(item.name, ''),
            category_id: String(firstDefined(item.category_id, item.category?.id, '')),
            price: firstDefined(item.price, ''),
            quantity: firstDefined(item.quantity, 0),
            low_stock_threshold: firstDefined(item.low_stock_threshold, item.lowStockThreshold, 10),
        });
        setModalOpen(true);
    }

    function closeModal() {
        if (form.processing) return;
        setModalOpen(false);
        setEditingItem(null);
        form.clearErrors();
    }

    function submit(event) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            category_id: data.category_id || null,
            price: data.price === '' ? null : data.price,
            quantity: data.quantity === '' ? null : data.quantity,
            low_stock_threshold: data.low_stock_threshold === '' ? null : data.low_stock_threshold,
        }));
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setModalOpen(false);
                setEditingItem(null);
            },
        };
        if (isEditing) form.put(resourcePath('items', editingItem.id), options);
        else form.post(paths.items, options);
    }

    function confirmArchive() {
        if (!archiveTarget) return;
        archiveForm.delete(resourcePath('items', archiveTarget.id), {
            preserveScroll: true,
            onSuccess: () => setArchiveTarget(null),
        });
    }

    return (
        <>
            <Head title="Items · InvControl" />
            <PageHeader
                eyebrow="Catalog"
                title="Items"
                description="Search products, review stock health, and keep item records current."
                actions={<Button type="button" onClick={openCreate}><Icon name="plus" size={18} />Add item</Button>}
            />

            <Card className="overflow-hidden">
                <FilterBar onReset={resetFilters}>
                    <SearchField value={search} onChange={setSearch} placeholder="Search name or SKU" />
                    <label className="form-group">
                        <span className="form-label">Category</span>
                        <SelectInput value={categoryId} onChange={(event) => setCategoryId(event.target.value)} aria-label="Filter by category">
                            <option value="">All categories</option>
                            {asArray(categories).map((category) => <option key={category.id} value={category.id}>{relationName(category, ['name', 'category_name'], 'Category')}</option>)}
                        </SelectInput>
                    </label>
                    <label className="flex min-h-11 items-center gap-3 self-end rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" className="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600" checked={lowStock} onChange={(event) => setLowStock(event.target.checked)} />
                        Low stock only
                    </label>
                </FilterBar>

                <div className="table-shell">
                    <table className="data-table">
                        <caption className="sr-only">Inventory items, stock quantities, and actions</caption>
                        <thead><tr><th>SKU</th><th>Item</th><th>Category</th><th className="text-right">Unit price</th><th className="text-right">On hand</th><th>Status</th><th className="text-right">Actions</th></tr></thead>
                        <tbody>
                            {rows.length ? rows.map((item) => {
                                const quantity = numberValue(item.quantity, item.current_quantity, item.stock_quantity);
                                const threshold = numberValue(item.low_stock_threshold, item.lowStockThreshold);
                                const archived = Boolean(firstDefined(item.is_deleted, item.isArchived, false));
                                return (
                                    <tr key={item.id} className={archived ? 'opacity-65' : ''}>
                                        <td><span className="font-mono text-xs font-semibold text-slate-700">{item.sku || '—'}</span></td>
                                        <td><span className="font-semibold text-slate-900">{item.name || 'Unnamed item'}</span></td>
                                        <td>{relationName(item.category, ['name', 'category_name'], item.category_name || 'Uncategorized')}</td>
                                        <td className="text-right font-mono text-xs">{formatCurrency(item.price)}</td>
                                        <td className="text-right"><span className="font-mono font-semibold text-slate-900">{formatNumber(quantity)}</span><span className="block text-xs text-slate-500">Reorder at {formatNumber(threshold)}</span></td>
                                        <td>{archived ? <Badge tone="slate">Archived</Badge> : <StockBadge quantity={quantity} threshold={threshold} />}</td>
                                        <td>
                                            <div className="flex justify-end gap-1 no-print">
                                                <button type="button" className="icon-button" onClick={() => openEdit(item)} aria-label={`Edit ${item.name || 'item'}`} title="Edit item"><Icon name="edit" size={18} /></button>
                                                {!archived && <button type="button" className="icon-button hover:!bg-amber-50 hover:!text-amber-800" onClick={() => setArchiveTarget(item)} aria-label={`Archive ${item.name || 'item'}`} title="Archive item"><Icon name="archive" size={18} /></button>}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : <TableState colSpan="7" loading={loading} empty error={String(pageErrors.items || '')} emptyTitle={search || categoryId || lowStock ? 'No items match these filters' : 'No items yet'} emptyDescription={search || categoryId || lowStock ? 'Try a broader search or clear a filter.' : 'Create the first item to begin tracking inventory.'} emptyIcon="box" />}
                        </tbody>
                    </table>
                </div>
                <Pagination meta={meta} currentQuery={{ search, category_id: categoryId, low_stock: lowStock ? '1' : '' }} />
            </Card>

            <Modal
                open={modalOpen}
                onClose={closeModal}
                title={isEditing ? 'Edit item' : 'Add item'}
                description={isEditing ? 'Update product details and its reorder threshold.' : 'Create an item record for stock tracking.'}
                footer={(
                    <>
                        <button type="button" className="btn btn-secondary" onClick={closeModal} disabled={form.processing}>Cancel</button>
                        <Button type="submit" form="item-form" busy={form.processing} disabled={form.processing}>{isEditing ? 'Save changes' : 'Create item'}</Button>
                    </>
                )}
            >
                <form id="item-form" onSubmit={submit} className="grid gap-5 sm:grid-cols-2" noValidate>
                    <FormField label="SKU" name="sku" errors={form.errors} required>
                        <TextInput name="sku" value={form.data.sku} onChange={(event) => form.setData('sku', event.target.value)} placeholder="MED-001" errors={form.errors} autoComplete="off" required />
                    </FormField>
                    <FormField label="Item name" name="name" errors={form.errors} required>
                        <TextInput name="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} placeholder="Nitrile gloves" errors={form.errors} required />
                    </FormField>
                    <FormField label="Category" name="category_id" errors={form.errors} required>
                        <SelectInput name="category_id" value={form.data.category_id} onChange={(event) => form.setData('category_id', event.target.value)} errors={form.errors} required>
                            <option value="">Select a category</option>
                            {asArray(categories).map((category) => <option key={category.id} value={category.id}>{relationName(category, ['name', 'category_name'], 'Category')}</option>)}
                        </SelectInput>
                    </FormField>
                    <FormField label="Unit price" name="price" errors={form.errors} hint="Use your operating currency." required>
                        <TextInput name="price" type="number" min="0" step="0.01" value={form.data.price} onChange={(event) => form.setData('price', event.target.value)} placeholder="0.00" errors={form.errors} required />
                    </FormField>
                    <FormField label="Opening quantity" name="quantity" errors={form.errors} hint="Use zero for a new out-of-stock item." required>
                        <TextInput name="quantity" type="number" min="0" step="1" value={form.data.quantity} onChange={(event) => form.setData('quantity', event.target.value)} placeholder="0" errors={form.errors} required />
                    </FormField>
                    <FormField label="Low-stock threshold" name="low_stock_threshold" errors={form.errors} required>
                        <TextInput name="low_stock_threshold" type="number" min="0" step="1" value={form.data.low_stock_threshold} onChange={(event) => form.setData('low_stock_threshold', event.target.value)} errors={form.errors} required />
                    </FormField>
                    {form.errors.general && <div className="sm:col-span-2"><FieldError errors={form.errors} name="general" /></div>}
                </form>
            </Modal>

            <ConfirmDialog
                open={Boolean(archiveTarget)}
                onClose={() => !archiveForm.processing && setArchiveTarget(null)}
                onConfirm={confirmArchive}
                busy={archiveForm.processing}
                tone="primary"
                title="Archive item?"
                confirmLabel="Archive item"
                message={`Archive “${archiveTarget?.name || 'this item'}”? Its history stays available, but it will be hidden from active item views.`}
            />
        </>
    );
}
