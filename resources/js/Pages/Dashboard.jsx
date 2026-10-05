import { Head, Link, usePage } from '@inertiajs/react';
import Icon from '../Components/Icons';
import { ActionBadge, Card, CardHeader, EmptyState, ErrorState, ExpiryBadge, MetricCard, Notice, PageHeader, StockBadge } from '../Components/UI';
import { asArray, firstDefined, formatCurrency, formatDate, formatNumber, numberValue, relationName, timeAgo, titleCase } from '../Utils';
import { paths } from '../Utils/routes';

function CategoryStockChart({ chart, categoryStock }) {
    const source = chart || categoryStock || {};
    let rows = Array.isArray(source)
        ? source.map((row) => ({ name: firstDefined(row.name, row.category_name, row.category, 'Uncategorized'), value: numberValue(row.quantity, row.total_quantity, row.stock, row.value) }))
        : (source.labels || []).map((name, index) => ({ name, value: numberValue(source.data?.[index], source.values?.[index], source.stock?.[index]) }));
    if (!rows.length) {
        const records = asArray(source.categories);
        rows = records.map((row) => ({ name: firstDefined(row.name, row.category_name, row.category?.name, 'Uncategorized'), value: numberValue(row.quantity, row.total_quantity, row.stock, row.value) }));
    }
    if (!rows.length) return <EmptyState compact icon="chart" title="No category stock yet" description="Add items and assign categories to see stock distribution." />;
    const sorted = rows.filter((row) => Number.isFinite(Number(row.value))).sort((a, b) => b.value - a.value);
    const max = Math.max(...sorted.map((row) => Number(row.value)), 1);
    const total = sorted.reduce((sum, row) => sum + Number(row.value), 0);

    return (
        <div>
            <div className="mb-5 flex items-center justify-between gap-4 rounded-lg bg-teal-50 px-3 py-2.5 text-sm">
                <p className="text-teal-900"><span className="font-semibold">{formatNumber(total)}</span> units across {sorted.length} categories</p>
                <span className="font-mono text-xs text-teal-700">Largest first</span>
            </div>
            <div className="chart-list" role="img" aria-label={`Stock quantity by category. ${sorted.map((row) => `${row.name}: ${formatNumber(row.value)}`).join(', ')}.`}>
                {sorted.map((row) => {
                    const width = Math.max(1, (Number(row.value) / max) * 100);
                    return (
                        <div key={String(row.name)} className="chart-row">
                            <p className="truncate text-sm font-medium text-slate-700" title={String(row.name)}>{String(row.name)}</p>
                            <div className="chart-track" aria-hidden="true"><div className="chart-bar" style={{ width: `${width}%` }} /></div>
                            <p className="min-w-12 text-right font-mono text-xs font-semibold text-slate-800">{formatNumber(row.value)}</p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export default function Dashboard({ metrics = {}, chart, categoryStock, recentActivity, recent_activity: legacyActivity, expiringBatches, expiring_batches: legacyBatches, error }) {
    const { props } = usePage();
    const pageError = error || props.error;
    const activityRows = asArray(recentActivity || legacyActivity);
    const batchRows = asArray(expiringBatches || legacyBatches);
    const values = {
        totalProducts: numberValue(metrics.total_products, metrics.totalProducts, metrics.total_items),
        totalValue: numberValue(metrics.total_value, metrics.totalValue, metrics.stock_value),
        lowStock: numberValue(metrics.low_stock_count, metrics.lowStockCount, metrics.low_stock),
        nearExpiry: numberValue(metrics.near_expiry_count, metrics.nearExpiryCount, metrics.expiring_count),
    };

    if (pageError) {
        return (
            <>
                <Head title="Dashboard · InvControl" />
                <PageHeader title="Inventory overview" description="A live view of stock health and operational activity." />
                <Card><ErrorState message={String(pageError)} onRetry={() => window.location.reload()} /></Card>
            </>
        );
    }

    return (
        <>
            <Head title="Dashboard · InvControl" />
            <PageHeader
                eyebrow="Operations pulse"
                title="Inventory overview"
                description="A focused view of stock health, expiry risk, and the changes that need your attention."
                actions={<Link href={paths.reports} className="btn btn-secondary"><Icon name="chart" size={17} />Open reports</Link>}
            />

            <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Inventory metrics">
                <MetricCard label="Active products" value={formatNumber(values.totalProducts)} hint="Across all categories" icon="package" tone="teal" />
                <MetricCard label="Stock value" value={formatCurrency(values.totalValue)} hint="Quantity × unit price" icon="chart" tone="blue" />
                <MetricCard label="Low-stock items" value={formatNumber(values.lowStock)} hint="At or below reorder threshold" icon="warning" tone="amber" />
                <MetricCard label="Expiring soon" value={formatNumber(values.nearExpiry)} hint="Requires an expiry decision" icon="clock" tone="red" />
            </section>

            <div className="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(20rem,0.75fr)]">
                <Card>
                    <CardHeader title="Stock by category" description="Current on-hand quantity, sorted from largest to smallest." actions={<Link href={paths.items} className="text-sm font-semibold text-teal-700 hover:text-teal-900">View items</Link>} />
                    <div className="p-4 sm:p-5"><CategoryStockChart chart={chart} categoryStock={categoryStock} /></div>
                </Card>

                <Card>
                    <CardHeader title="Recent activity" description="The latest inventory changes." actions={<Link href={paths.activityLogs} className="text-sm font-semibold text-teal-700 hover:text-teal-900">View log</Link>} />
                    {activityRows.length ? (
                        <ul className="divide-y divide-slate-100">
                            {activityRows.slice(0, 6).map((activity, index) => {
                                const change = firstDefined(activity.quantity_change, activity.change);
                                const hasBefore = activity.old_quantity !== undefined && activity.old_quantity !== null;
                                const hasAfter = activity.new_quantity !== undefined && activity.new_quantity !== null;
                                const computed = hasBefore && hasAfter
                                    ? Number(activity.new_quantity) - Number(activity.old_quantity)
                                    : numberValue(change);
                                return (
                                    <li key={activity.id || index} className="flex items-start gap-3 px-4 py-3.5 sm:px-5">
                                        <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600"><Icon name="history" size={16} /></span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-semibold text-slate-900">{relationName(activity.item, ['name', 'item_name'], titleCase(firstDefined(activity.item_name, 'Inventory change')))}</p>
                                            <p className="mt-0.5 truncate text-xs text-slate-500">{firstDefined(activity.user?.name, activity.user_name, activity.user?.email, 'System')} · {timeAgo(activity.created_at)}</p>
                                        </div>
                                        <div className="text-right">
                                            <ActionBadge action={activity.action_type || activity.action} />
                                            {computed !== 0 && <p className={`mt-1 font-mono text-xs font-semibold ${computed > 0 ? 'text-emerald-700' : 'text-red-700'}`}>{computed > 0 ? '+' : ''}{formatNumber(computed)}</p>}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    ) : <EmptyState compact icon="history" title="No recent activity" description="Inventory changes will appear here as the team works." />}
                </Card>
            </div>

            <Card className="mt-5 overflow-hidden">
                <CardHeader title="Expiry watchlist" description="Batches ordered by the date they need attention." actions={<Link href={paths.batches} className="text-sm font-semibold text-teal-700 hover:text-teal-900">Manage batches</Link>} />
                <div className="table-shell">
                    <table className="data-table">
                        <thead><tr><th>SKU</th><th>Item</th><th>Batch</th><th className="text-right">Quantity</th><th>Expiry</th><th>Urgency</th></tr></thead>
                        <tbody>
                            {batchRows.length ? batchRows.map((batch, index) => {
                                const quantity = numberValue(batch.quantity, batch.current_quantity);
                                const threshold = numberValue(batch.low_stock_threshold);
                                return (
                                    <tr key={batch.id || index}>
                                        <td><span className="font-mono text-xs font-semibold text-slate-700">{firstDefined(batch.sku, batch.item?.sku, '—')}</span></td>
                                        <td><span className="font-semibold text-slate-900">{firstDefined(batch.item?.name, batch.item_name, batch.name, '—')}</span></td>
                                        <td><span className="font-mono text-xs text-slate-600">{firstDefined(batch.batch_number, batch.batch?.batch_number, '—')}</span></td>
                                        <td className="text-right"><div className="inline-flex min-w-24 flex-col items-end gap-1.5"><span className="font-mono font-semibold text-slate-900">{formatNumber(quantity)}</span><span className="stock-meter w-24"><span className={`stock-meter-fill ${quantity === 0 ? 'stock-meter-empty' : quantity <= threshold ? 'stock-meter-low' : ''}`} style={{ width: `${Math.min(100, threshold > 0 ? (quantity / (threshold * 3)) * 100 : 100)}%` }} /></span></div></td>
                                        <td className="whitespace-nowrap">{formatDate(firstDefined(batch.expiry_date, batch.expires_at))}</td>
                                        <td><ExpiryBadge date={firstDefined(batch.days_until_expiry, batch.days_until)} days={firstDefined(batch.days_until_expiry, batch.days_until)} status={batch.urgency_status} /></td>
                                    </tr>
                                );
                            }) : (
                                <tr><td colSpan="6"><EmptyState compact icon="clock" title="No batches approaching expiry" description="You are clear for now. New risks will appear here." /></td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </Card>

            {values.lowStock > 0 && (
                <Notice tone="warning" className="mt-5" title="Reorder attention needed">
                    {formatNumber(values.lowStock)} item{values.lowStock === 1 ? ' is' : 's are'} at or below the reorder threshold.{' '}
                    <Link href={`${paths.items}?low_stock=1`} className="font-semibold underline underline-offset-2">Review low-stock items</Link>.
                </Notice>
            )}
        </>
    );
}
