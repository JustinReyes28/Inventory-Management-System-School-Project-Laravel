import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Icon from '../../Components/Icons';
import { FieldError, FormField, SelectInput, TextInput } from '../../Components/FormField';
import Modal, { ConfirmDialog } from '../../Components/Modal';
import Pagination from '../../Components/Pagination';
import useInertiaLoading from '../../Hooks/useInertiaLoading';
import { Badge, Button, Card, FilterBar, PageHeader, SearchField, TableState } from '../../Components/UI';
import { asArray, expiryDays, firstDefined, formatDate, formatNumber, normalizePagination, numberValue, relationName } from '../../Utils';
import { paths, resourcePath } from '../../Utils/routes';

const blankBatch = { item_id: '', batch_number: '', quantity: '', expiry_date: '' };
const statusTabs = [
    { value: '', label: 'All statuses' },
    { value: 'safe', label: 'Safe' },
    { value: 'near_expiry', label: 'Near expiry' },
    { value: 'expired', label: 'Expired' },
];

function batchStatus(batch) {
    const calculatedDays = expiryDays(firstDefined(batch.expiry_date, batch.expires_at));
    const days = numberValue(firstDefined(batch.days_until_expiry, calculatedDays), 0);
    if (days < 0) return { value: 'expired', label: 'Expired', tone: 'red' };
    if (days <= 30) return { value: 'near_expiry', label: 'Near expiry', tone: 'amber' };
    return { value: 'safe', label: 'Safe', tone: 'green' };
}

export default function BatchesIndex({ batches, items = [], filters = {}, errors: pageErrors = {} }) {
    const loading = useInertiaLoading();
    const urlFilters = new URLSearchParams(window.location.search);
    const [search, setSearch] = useState(firstDefined(filters.search, urlFilters.get('search'), ''));
    const [itemId, setItemId] = useState(String(firstDefined(filters.item_id, filters.itemId, urlFilters.get('item_id'), '')));
    const [status, setStatus] = useState(firstDefined(filters.status, urlFilters.get('status'), ''));
    const [modalOpen, setModalOpen] = useState(false);
    const [editingBatch, setEditingBatch] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const lastRequest = useRef(JSON.stringify({ item_id: firstDefined(filters.item_id, '') }));
    const form = useForm({ ...blankBatch });
    const deleteForm = useForm({});
    const rows = asArray(batches);
    const meta = normalizePagination(batches, Number(filters.page) || 1);
    const isEditing = Boolean(editingBatch?.id);

    const filteredRows = useMemo(() => {
        const query = search.trim().toLowerCase();
        return rows.filter((batch) => {
            const matchesSearch = !query || `${firstDefined(batch.batch_number, '')} ${firstDefined(batch.item_name, batch.item?.name, '')}`.toLowerCase().includes(query);
            const matchesStatus = !status || batchStatus(batch).value === status;
            return matchesSearch && matchesStatus;
        });
    }, [rows, search, status]);

    useEffect(() => {
        const next = { item_id: itemId, page: '' };
        const signature = JSON.stringify(next);
        if (signature === lastRequest.current) return;
        lastRequest.current = signature;
        router.get(paths.batches, next, { preserveState: true, preserveScroll: true, replace: true });
    }, [itemId]);

    function resetFilters() {
        setSearch('');
        setItemId('');
        setStatus('');
    }

    function openCreate() {
        setEditingBatch(null);
        form.setData({ ...blankBatch });
        form.clearErrors();
        setModalOpen(true);
    }

    function openEdit(batch) {
        setEditingBatch(batch);
        form.clearErrors();
        form.setData({
            item_id: String(firstDefined(batch.item_id, batch.item?.id, '')),
            batch_number: firstDefined(batch.batch_number, batch.batch?.batch_number, ''),
            quantity: firstDefined(batch.quantity, batch.current_quantity, 0),
            expiry_date: firstDefined(batch.expiry_date, batch.expires_at, ''),
        });
        setModalOpen(true);
    }

    function closeModal() {
        if (form.processing) return;
        setModalOpen(false);
        setEditingBatch(null);
        form.clearErrors();
    }

    function submit(event) {
        event.preventDefault();
        form.transform((data) => ({ ...data, item_id: data.item_id || null, quantity: data.quantity === '' ? null : data.quantity, expiry_date: data.expiry_date || null }));
        const options = { preserveScroll: true, onSuccess: () => { setModalOpen(false); setEditingBatch(null); } };
        if (isEditing) form.put(resourcePath('batches', editingBatch.id), options);
        else form.post(paths.batches, options);
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        deleteForm.delete(resourcePath('batches', deleteTarget.id), { preserveScroll: true, onSuccess: () => setDeleteTarget(null) });
    }

    return (
        <>
            <Head title="Batches · InvControl" />
            <PageHeader
                eyebrow="Lot tracking"
                title="Batches"
                description="Track quantities and expiry dates for every lot in stock."
                actions={<Button type="button" onClick={openCreate}><Icon name="plus" size={18} />Add batch</Button>}
            />

            <Card className="overflow-hidden">
                <FilterBar onReset={resetFilters}>
                    <SearchField value={search} onChange={setSearch} placeholder="Search batch or item" />
                    <label className="form-group">
                        <span className="form-label">Item</span>
                        <SelectInput value={itemId} onChange={(event) => setItemId(event.target.value)} aria-label="Filter by item">
                            <option value="">All items</option>
                            {asArray(items).map((item) => <option key={item.id} value={item.id}>{item.name}{item.sku ? ` (${item.sku})` : ''}</option>)}
                        </SelectInput>
                    </label>
                    <div className="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-2" role="group" aria-label="Filter batches by status">
                        {statusTabs.map((option) => (
                            <button
                                key={option.value || 'all'}
                                type="button"
                                className={`btn btn-sm ${status === option.value ? 'btn-primary' : 'btn-secondary'}`}
                                aria-pressed={status === option.value}
                                onClick={() => setStatus(option.value)}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </FilterBar>

                <div className="table-shell">
                    <table className="data-table">
                        <caption className="sr-only">Inventory batches, quantities, and expiry status</caption>
                        <thead><tr><th>Batch</th><th>Item</th><th className="text-right">Quantity</th><th>Expiry date</th><th>Status</th><th>Created</th><th className="text-right">Actions</th></tr></thead>
                        <tbody>
                            {filteredRows.length ? filteredRows.map((batch) => {
                                const resolved = batchStatus(batch);
                                return (
                                    <tr key={batch.id}>
                                        <td><span className="font-mono text-xs font-semibold text-slate-800">{firstDefined(batch.batch_number, batch.batch?.batch_number, `#${batch.id}`)}</span></td>
                                        <td>
                                            <span className="font-semibold text-slate-900">{relationName(batch.item, ['name', 'item_name'], batch.item_name || 'Unknown item')}</span>
                                            <span className="block font-mono text-xs text-slate-500">{firstDefined(batch.item?.sku, batch.sku, '')}</span>
                                        </td>
                                        <td className="text-right font-mono font-semibold text-slate-900">{formatNumber(firstDefined(batch.quantity, batch.current_quantity))}</td>
                                        <td className="whitespace-nowrap">{formatDate(firstDefined(batch.expiry_date, batch.expires_at))}</td>
                                        <td><Badge tone={resolved.tone} dot>{firstDefined(batch.status_label, resolved.label)}</Badge></td>
                                        <td className="whitespace-nowrap text-xs text-slate-500">{formatDate(firstDefined(batch.created_at, batch.createdAt))}</td>
                                        <td>
                                            <div className="flex justify-end gap-1 no-print">
                                                <button type="button" className="icon-button" onClick={() => openEdit(batch)} aria-label={`Edit batch ${batch.batch_number || batch.id}`} title="Edit batch"><Icon name="edit" size={18} /></button>
                                                <button type="button" className="icon-button hover:!bg-red-50 hover:!text-red-700" onClick={() => setDeleteTarget(batch)} aria-label={`Delete batch ${batch.batch_number || batch.id}`} title="Delete batch"><Icon name="trash" size={18} /></button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : <TableState colSpan="7" loading={loading} empty error={String(pageErrors.batches || '')} emptyTitle={search || itemId || status ? 'No batches match these filters' : 'No batches yet'} emptyDescription={search || itemId || status ? 'Clear one or more filters to broaden the results.' : 'Add a batch to start tracking lot quantities and expiry.'} emptyIcon="calendar" />}
                        </tbody>
                    </table>
                </div>
                <Pagination meta={meta} currentQuery={{ search, item_id: itemId, status }} />
            </Card>

            <Modal
                open={modalOpen}
                onClose={closeModal}
                title={isEditing ? 'Edit batch' : 'Add batch'}
                description="Record the lot quantity and date through which the batch should be used."
                footer={(
                    <>
                        <button type="button" className="btn btn-secondary" onClick={closeModal} disabled={form.processing}>Cancel</button>
                        <Button type="submit" form="batch-form" busy={form.processing}>{isEditing ? 'Save changes' : 'Create batch'}</Button>
                    </>
                )}
            >
                <form id="batch-form" onSubmit={submit} className="grid gap-5 sm:grid-cols-2" noValidate>
                    <FormField label="Item" name="item_id" errors={form.errors} required className="sm:col-span-2">
                        <SelectInput name="item_id" value={form.data.item_id} onChange={(event) => form.setData('item_id', event.target.value)} errors={form.errors} required>
                            <option value="">Select an item</option>
                            {asArray(items).map((item) => <option key={item.id} value={item.id}>{item.name}{item.sku ? ` (${item.sku})` : ''}</option>)}
                        </SelectInput>
                    </FormField>
                    <FormField label="Batch number" name="batch_number" errors={form.errors} required>
                        <TextInput name="batch_number" value={form.data.batch_number} onChange={(event) => form.setData('batch_number', event.target.value)} placeholder="LOT-2026-014" errors={form.errors} required />
                    </FormField>
                    <FormField label="Quantity" name="quantity" errors={form.errors} required>
                        <TextInput name="quantity" type="number" min="0" step="1" value={form.data.quantity} onChange={(event) => form.setData('quantity', event.target.value)} errors={form.errors} required />
                    </FormField>
                    <FormField label="Expiry date" name="expiry_date" errors={form.errors} required>
                        <TextInput name="expiry_date" type="date" value={form.data.expiry_date} onChange={(event) => form.setData('expiry_date', event.target.value)} errors={form.errors} required />
                    </FormField>
                    {form.errors.general && <div className="sm:col-span-2"><FieldError errors={form.errors} name="general" /></div>}
                </form>
            </Modal>

            <ConfirmDialog
                open={Boolean(deleteTarget)}
                onClose={() => !deleteForm.processing && setDeleteTarget(null)}
                onConfirm={confirmDelete}
                busy={deleteForm.processing}
                title="Delete batch?"
                confirmLabel="Delete batch"
                message={`Delete batch “${firstDefined(deleteTarget?.batch_number, `#${deleteTarget?.id || ''}`)}”? This removes the lot from active stock tracking.`}
            />
        </>
    );
}
