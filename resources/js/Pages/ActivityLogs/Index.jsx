import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Icon from '../../Components/Icons';
import Pagination from '../../Components/Pagination';
import useInertiaLoading from '../../Hooks/useInertiaLoading';
import { ActionBadge, Card, EmptyState, FilterBar, PageHeader, TableState } from '../../Components/UI';
import { asArray, firstDefined, formatDateTime, formatNumber, normalizePagination, numberValue, relationName } from '../../Utils';
import { paths } from '../../Utils/routes';

const actionLabels = {
    create: 'Created',
    update: 'Updated',
    delete: 'Deleted',
    stock_in: 'Stock in',
    stock_out: 'Stock out',
};

export default function ActivityLogsIndex({ logs, users = [], filters = {}, pagination, actionTypes = actionLabels, errors: pageErrors = {} }) {
    const loading = useInertiaLoading();
    const urlFilters = new URLSearchParams(window.location.search);
    const [filtersState, setFiltersState] = useState({
        user_id: String(firstDefined(filters.user_id, filters.userId, urlFilters.get('user_id'), '')),
        action_type: firstDefined(filters.action_type, filters.actionType, urlFilters.get('action_type'), ''),
        date_from: firstDefined(filters.date_from, filters.dateFrom, urlFilters.get('date_from'), ''),
        date_to: firstDefined(filters.date_to, filters.dateTo, urlFilters.get('date_to'), ''),
    });
    const lastRequest = useRef(JSON.stringify(filtersState));
    const rows = asArray(logs);
    const meta = normalizePagination(pagination || logs, Number(firstDefined(filters.page, urlFilters.get('page'), 1)));


    useEffect(() => {
        const timer = window.setTimeout(() => {
            const next = { ...filtersState, page: '' };
            const signature = JSON.stringify(next);
            if (signature === lastRequest.current) return;
            lastRequest.current = signature;
            router.get(paths.activityLogs, next, { preserveState: true, preserveScroll: true, replace: true });
        }, 250);
        return () => window.clearTimeout(timer);
    }, [filtersState]);

    function updateFilter(name, value) {
        setFiltersState((current) => ({ ...current, [name]: value }));
    }

    function resetFilters() {
        setFiltersState({ user_id: '', action_type: '', date_from: '', date_to: '' });
    }

    return (
        <>
            <Head title="Activity logs · InvControl" />
            <PageHeader eyebrow="Audit trail" title="Activity logs" description="Review who changed inventory, what changed, and when it happened." />

            <Card className="overflow-hidden">
                <FilterBar onReset={resetFilters}>
                    <label className="form-group">
                        <span className="form-label">User</span>
                        <select className="form-input" value={filtersState.user_id} onChange={(event) => updateFilter('user_id', event.target.value)}>
                            <option value="">All users</option>
                            {asArray(users).map((user) => <option key={user.id} value={user.id}>{relationName(user, ['name', 'full_name'], user.email || `User #${user.id}`)}</option>)}
                        </select>
                    </label>
                    <label className="form-group">
                        <span className="form-label">Action</span>
                        <select className="form-input" value={filtersState.action_type} onChange={(event) => updateFilter('action_type', event.target.value)}>
                            <option value="">All actions</option>
                            {Object.entries(actionTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                    </label>
                    <label className="form-group">
                        <span className="form-label">From date</span>
                        <input type="date" className="form-input" value={filtersState.date_from} max={filtersState.date_to || undefined} onChange={(event) => updateFilter('date_from', event.target.value)} />
                    </label>
                    <label className="form-group">
                        <span className="form-label">To date</span>
                        <input type="date" className="form-input" value={filtersState.date_to} min={filtersState.date_from || undefined} onChange={(event) => updateFilter('date_to', event.target.value)} />
                    </label>
                </FilterBar>

                <div className="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-sm text-slate-600">
                    <p><span className="font-semibold text-slate-900">{formatNumber(meta.total)}</span> matching events</p>
                    {loading && <span className="flex items-center gap-2 text-xs"><span className="spinner" aria-hidden="true" />Updating…</span>}
                </div>

                <div className="table-shell">
                    <table className="data-table">
                        <caption className="sr-only">Inventory activity audit log</caption>
                        <thead><tr><th>Event</th><th>User</th><th>Item</th><th>Action</th><th className="text-right">Before</th><th className="text-right">After</th><th>Description</th><th>Date & time</th></tr></thead>
                        <tbody>
                            {rows.length ? rows.map((log, index) => {
                                const before = firstDefined(log.old_quantity, log.previous_quantity);
                                const after = firstDefined(log.new_quantity, log.current_quantity);
                                const hasChange = before !== undefined && before !== null && after !== undefined && after !== null;
                                return (
                                    <tr key={log.id || index}>
                                        <td><span className="font-mono text-xs text-slate-500">#{log.id || '—'}</span></td>
                                        <td><span className="font-semibold text-slate-900">{firstDefined(log.user?.name, log.user_name, log.user?.email, 'System')}</span></td>
                                        <td><span className="text-slate-700">{relationName(log.item, ['name', 'item_name'], log.item_name || '—')}</span></td>
                                        <td><ActionBadge action={log.action_type || log.action} /></td>
                                        <td className="text-right font-mono text-xs">{before === undefined || before === null ? '—' : formatNumber(before)}</td>
                                        <td className="text-right font-mono text-xs font-semibold text-slate-900">{after === undefined || after === null ? '—' : formatNumber(after)}{hasChange && Number(after) !== Number(before) && <span className={`ml-2 ${Number(after) > Number(before) ? 'text-emerald-700' : 'text-red-700'}`}>{Number(after) > Number(before) ? '+' : ''}{formatNumber(Number(after) - Number(before))}</span>}</td>
                                        <td><p className="max-w-xs whitespace-normal text-xs leading-5 text-slate-600" title={log.description || ''}>{log.description || <span className="text-slate-500">No description</span>}</p></td>
                                        <td className="whitespace-nowrap text-xs text-slate-500">{formatDateTime(log.created_at)}</td>
                                    </tr>
                                );
                            }) : <TableState colSpan="8" loading={loading} empty error={String(pageErrors.logs || '')} emptyTitle="No activity matches these filters" emptyDescription="Adjust the user, action, or date range to see more events." emptyIcon="history" />}
                        </tbody>
                    </table>
                </div>
                <Pagination meta={meta} currentQuery={filtersState} />
            </Card>

            <div className="mt-4 flex items-start gap-2 text-xs leading-5 text-slate-500">
                <Icon name="info" size={16} className="mt-0.5 shrink-0" />
                <p>Activity records are retained for accountability. Filtered views are loaded from the server and paginated.</p>
            </div>
        </>
    );
}
