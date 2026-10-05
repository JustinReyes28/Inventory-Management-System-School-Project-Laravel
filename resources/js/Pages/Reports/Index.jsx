import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import Icon from '../../Components/Icons';
import { Badge, Button, Card, EmptyState, ExpiryBadge, MetricCard, PageHeader, StockBadge, TableState, Tabs } from '../../Components/UI';
import { asArray, expiryDays, firstDefined, formatCurrency, formatDate, formatNumber, numberValue, relationName, titleCase } from '../../Utils';
import { paths } from '../../Utils/routes';

const reportTabs = [
    { value: 'low_stock', label: 'Low stock', icon: 'warning' },
    { value: 'expiry', label: 'Expiry', icon: 'clock' },
    { value: 'activity_summary', label: 'Activity summary', icon: 'history' },
];

const windowOptions = [7, 14, 30, 60, 90];

function countValue(record, ...keys) {
    return numberValue(...keys.map((key) => record?.[key]));
}

function LowStockTable({ rows, categoryId, onCategoryChange, categories }) {
    return (
        <>
            <div className="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-end sm:justify-between no-print">
                <label className="form-group w-full sm:max-w-xs">
                    <span className="form-label">Category</span>
                    <select className="form-input" value={categoryId} onChange={(event) => onCategoryChange(event.target.value)}>
                        <option value="">All categories</option>
                        {asArray(categories).map((category) => <option key={category.id} value={category.id}>{relationName(category, ['name', 'category_name'], 'Category')}</option>)}
                    </select>
                </label>
                <p className="text-sm text-slate-600"><span className="font-semibold text-slate-900">{rows.length}</span> items need attention</p>
            </div>
            <div className="table-shell">
                <table className="data-table">
                    <caption className="sr-only">Low-stock report</caption>
                    <thead><tr><th>SKU</th><th>Item</th><th>Category</th><th className="text-right">Unit price</th><th className="text-right">On hand</th><th className="text-right">Reorder at</th><th className="text-right">Stock value</th><th>Status</th></tr></thead>
                    <tbody>
                        {rows.length ? rows.map((item, index) => {
                            const quantity = numberValue(item.quantity, item.current_quantity);
                            const threshold = numberValue(item.low_stock_threshold);
                            return (
                                <tr key={item.id || index}>
                                    <td><span className="font-mono text-xs font-semibold text-slate-700">{item.sku || '—'}</span></td>
                                    <td><span className="font-semibold text-slate-900">{item.name || item.item_name || 'Unnamed item'}</span></td>
                                    <td>{relationName(item.category, ['name', 'category_name'], item.category_name || 'Uncategorized')}</td>
                                    <td className="text-right font-mono text-xs">{formatCurrency(item.price)}</td>
                                    <td className="text-right font-mono font-semibold text-slate-900">{formatNumber(quantity)}</td>
                                    <td className="text-right font-mono text-xs text-slate-500">{formatNumber(threshold)}</td>
                                    <td className="text-right font-mono text-xs font-semibold text-slate-700">{formatCurrency(firstDefined(item.stock_value, Number(item.price) * quantity))}</td>
                                    <td><StockBadge quantity={quantity} threshold={threshold} /></td>
                                </tr>
                            );
                        }) : <TableState colSpan="8" empty emptyTitle="No low-stock items" emptyDescription="This category has no items at or below their reorder threshold." emptyIcon="checkCircle" />}
                    </tbody>
                </table>
            </div>
        </>
    );
}

function ExpiryTable({ rows, days, onDaysChange }) {
    return (
        <>
            <div className="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between no-print">
                <div className="flex flex-wrap gap-2" role="group" aria-label="Expiry report window">
                    {windowOptions.map((option) => (
                        <button key={option} type="button" className={`btn btn-sm ${days === option ? 'btn-primary' : 'btn-secondary'}`} aria-pressed={days === option} onClick={() => onDaysChange(option)}>{option} days</button>
                    ))}
                </div>
                <p className="text-sm text-slate-600"><span className="font-semibold text-slate-900">{rows.length}</span> batches in range</p>
            </div>
            <div className="table-shell">
                <table className="data-table">
                    <caption className="sr-only">Batch expiry report</caption>
                    <thead><tr><th>SKU</th><th>Item</th><th>Batch</th><th>Category</th><th className="text-right">Quantity</th><th>Expiry</th><th className="text-right">Days</th><th>Urgency</th></tr></thead>
                    <tbody>
                        {rows.length ? rows.map((batch, index) => {
                            const remaining = firstDefined(batch.days_until_expiry, expiryDays(firstDefined(batch.expiry_date, batch.expires_at)), 0);
                            return (
                                <tr key={batch.id || index}>
                                    <td><span className="font-mono text-xs font-semibold text-slate-700">{firstDefined(batch.sku, batch.item?.sku, '—')}</span></td>
                                    <td><span className="font-semibold text-slate-900">{firstDefined(batch.item?.name, batch.item_name, batch.name, '—')}</span></td>
                                    <td><span className="font-mono text-xs text-slate-600">{batch.batch_number || '—'}</span></td>
                                    <td>{relationName(batch.category, ['name', 'category_name'], firstDefined(batch.category_name, batch.item?.category?.name, 'Uncategorized'))}</td>
                                    <td className="text-right font-mono font-semibold">{formatNumber(firstDefined(batch.quantity, batch.current_quantity))}</td>
                                    <td className="whitespace-nowrap">{formatDate(firstDefined(batch.expiry_date, batch.expires_at))}</td>
                                    <td className={`text-right font-mono text-xs font-semibold ${Number(remaining) < 0 ? 'text-red-700' : 'text-slate-800'}`}>{Number(remaining) < 0 ? 'Expired' : formatNumber(remaining)}</td>
                                    <td><ExpiryBadge status={batch.urgency_status} days={remaining} /></td>
                                </tr>
                            );
                        }) : <TableState colSpan="8" empty emptyTitle="No batches in this window" emptyDescription="Try a longer expiry window or return later as dates approach." emptyIcon="clock" />}
                    </tbody>
                </table>
            </div>
        </>
    );
}

function ActivitySummaryTable({ rows }) {
    return (
        <div className="table-shell">
            <table className="data-table">
                <caption className="sr-only">Activity summary by user with create, update, delete, stock in, stock out, and total columns</caption>
                <thead><tr><th>User</th><th>Role</th><th className="text-center">Create</th><th className="text-center">Update</th><th className="text-center">Delete</th><th className="text-center">Stock in</th><th className="text-center">Stock out</th><th className="text-center">Total</th></tr></thead>
                <tbody>
                    {rows.length ? rows.map((summary, index) => {
                        const user = summary.user || {};
                        const role = firstDefined(summary.role_name, summary.role?.name, summary.role, 'Team member');
                        return (
                            <tr key={firstDefined(summary.user_id, user.id, index)}>
                                <td><span className="font-semibold text-slate-900">{firstDefined(summary.full_name, summary.user_name, user.name, user.email, 'Unknown user')}</span></td>
                                <td><Badge tone="slate">{titleCase(role)}</Badge></td>
                                <td className="text-center font-mono text-xs font-semibold text-teal-800">{formatNumber(countValue(summary, 'create', 'created', 'create_count', 'creates'))}</td>
                                <td className="text-center font-mono text-xs font-semibold text-blue-800">{formatNumber(countValue(summary, 'update', 'updated', 'update_count', 'updates'))}</td>
                                <td className="text-center font-mono text-xs font-semibold text-red-800">{formatNumber(countValue(summary, 'delete', 'deleted', 'delete_count', 'deletes'))}</td>
                                <td className="text-center font-mono text-xs font-semibold text-emerald-800">{formatNumber(countValue(summary, 'stock_in', 'stockIn', 'stock_in_count'))}</td>
                                <td className="text-center font-mono text-xs font-semibold text-amber-800">{formatNumber(countValue(summary, 'stock_out', 'stockOut', 'stock_out_count'))}</td>
                                <td className="text-center"><span className="inline-flex min-w-9 justify-center rounded-md bg-slate-900 px-2 py-1 font-mono text-xs font-bold text-white">{formatNumber(firstDefined(summary.total, summary.total_actions, summary.actions_count, 0))}</span></td>
                            </tr>
                        );
                    }) : <TableState colSpan="8" empty emptyTitle="No activity to summarize" emptyDescription="User activity totals will appear after inventory changes are recorded." emptyIcon="history" />}
                </tbody>
            </table>
        </div>
    );
}

export default function ReportsIndex({ overview = {}, lowStock, low_stock: legacyLowStock, expiry, expiringBatches, activitySummary, activity_summary: legacyActivity, tab, filters = {}, categories = [] }) {
    const normalizedTab = tab === 'low-stock' ? 'low_stock' : (tab === 'activity' ? 'activity_summary' : tab);
    const initialTab = reportTabs.some((item) => item.value === normalizedTab) ? normalizedTab : 'low_stock';
    const [activeTab, setActiveTab] = useState(initialTab);
    const [categoryId, setCategoryId] = useState(String(firstDefined(filters.category_id, filters.categoryId, '')));
    const [days, setDays] = useState(Number(firstDefined(filters.days, 30)) || 30);
    const lastRequest = useRef(JSON.stringify({ tab: initialTab, category_id: categoryId, days }));

    useEffect(() => {
        const next = { tab: activeTab, category_id: categoryId, days };
        const signature = JSON.stringify(next);
        if (signature === lastRequest.current) return;
        lastRequest.current = signature;
        router.get(paths.reports, next, { preserveState: true, preserveScroll: true, replace: true });
    }, [activeTab, categoryId, days]);

    const lowRows = useMemo(() => asArray(lowStock || legacyLowStock).filter((item) => !categoryId || String(firstDefined(item.category_id, item.category?.id, '')) === categoryId), [lowStock, legacyLowStock, categoryId]);
    const expiryRows = useMemo(() => asArray(expiry || expiringBatches).filter((batch) => {
        const remaining = numberValue(firstDefined(batch.days_until_expiry, expiryDays(firstDefined(batch.expiry_date, batch.expires_at)), Number.MAX_SAFE_INTEGER));
        return remaining <= days;
    }).sort((a, b) => numberValue(firstDefined(a.days_until_expiry, expiryDays(a.expiry_date))) - numberValue(firstDefined(b.days_until_expiry, expiryDays(b.expiry_date)))), [expiry, expiringBatches, days]);
    const activityRows = asArray(activitySummary || legacyActivity);
    const metrics = {
        low: numberValue(overview.low_stock_count, overview.lowStockCount, lowRows.length),
        expiring: numberValue(overview.expiring_30, overview.expiring30, overview.expiring_count),
        actions: numberValue(overview.total_actions, overview.totalActions),
    };

    return (
        <>
            <Head title="Reports · InvControl" />
            <PageHeader
                eyebrow="Decision support"
                title="Reports"
                description="Review reorder risk, expiry exposure, and a consistent summary of team activity."
                actions={<Button type="button" variant="secondary" onClick={() => window.print()}><Icon name="print" size={17} />Print report</Button>}
            />

            <section className="mb-5 grid gap-3 sm:grid-cols-3" aria-label="Report overview">
                <MetricCard label="Low-stock items" value={formatNumber(metrics.low)} hint="At or below reorder threshold" icon="warning" tone="amber" />
                <MetricCard label="Expiring within 30 days" value={formatNumber(metrics.expiring)} hint="Lots requiring a decision" icon="clock" tone="red" />
                <MetricCard label="Recorded actions" value={formatNumber(metrics.actions)} hint="Inventory activity events" icon="history" tone="teal" />
            </section>

            <Card className="overflow-hidden print:border-0 print:shadow-none">
                <Tabs tabs={reportTabs} active={activeTab} onChange={setActiveTab} label="Inventory report views" />
                <div id="panel-low_stock" role="tabpanel" aria-labelledby="tab-low_stock" tabIndex="0" hidden={activeTab !== 'low_stock'} data-active={activeTab === 'low_stock'} className="focus-visible:outline-none">
                    <LowStockTable rows={lowRows} categoryId={categoryId} onCategoryChange={setCategoryId} categories={categories} />
                </div>
                <div id="panel-expiry" role="tabpanel" aria-labelledby="tab-expiry" tabIndex="0" hidden={activeTab !== 'expiry'} data-active={activeTab === 'expiry'} className="focus-visible:outline-none">
                    <ExpiryTable rows={expiryRows} days={days} onDaysChange={setDays} />
                </div>
                <div id="panel-activity_summary" role="tabpanel" aria-labelledby="tab-activity_summary" tabIndex="0" hidden={activeTab !== 'activity_summary'} data-active={activeTab === 'activity_summary'} className="focus-visible:outline-none">
                    <ActivitySummaryTable rows={activityRows} />
                </div>
            </Card>
        </>
    );
}
