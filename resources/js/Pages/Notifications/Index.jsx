import { Head, Link, useForm } from '@inertiajs/react';
import Icon from '../../Components/Icons';
import Pagination from '../../Components/Pagination';
import { Badge, Button, Card, EmptyState, PageHeader } from '../../Components/UI';
import { asArray, firstDefined, formatDateTime, isUnread, normalizePagination, safeInternalUrl, timeAgo, titleCase } from '../../Utils';
import { notificationReadPath, notificationsReadAllPath } from '../../Utils/routes';

function notificationTone(type) {
    const value = String(type || '').toLowerCase();
    if (value.includes('stock_out') || value.includes('delete')) return 'red';
    if (value.includes('low_stock') || value.includes('stock_out')) return 'amber';
    if (value.includes('stock_in') || value.includes('create')) return 'green';
    if (value.includes('expiry')) return 'amber';
    return 'blue';
}

function notificationIcon(type) {
    const value = String(type || '').toLowerCase();
    if (value.includes('expiry')) return 'clock';
    if (value.includes('stock') || value.includes('quantity')) return 'box';
    if (value.includes('delete')) return 'trash';
    if (value.includes('create') || value.includes('stock_in')) return 'plus';
    if (value.includes('update') || value.includes('stock_out')) return 'edit';
    return 'bell';
}

export default function NotificationsIndex({ notifications, pagination, unreadCount, unread_count, filters = {}, errors: pageErrors = {} }) {
    const markForm = useForm({});
    const markAllForm = useForm({});
    const rows = asArray(notifications);
    const meta = normalizePagination(pagination || notifications, Number(firstDefined(filters.page, 1)));
    const totalUnread = Number(firstDefined(unreadCount, unread_count, 0)) || 0;

    function markRead(notification) {
        markForm.setData('id', notification.id);
        markForm.post(notificationReadPath(notification.id), {}, { preserveScroll: true, preserveState: true });
    }

    function markAllRead() {
        markAllForm.post(notificationsReadAllPath, {}, { preserveScroll: true, preserveState: true });
    }

    return (
        <>
            <Head title="Notifications · InvControl" />
            <PageHeader
                eyebrow="Attention queue"
                title="Notifications"
                description="Review low-stock, expiry, and inventory activity alerts in one place."
                actions={(
                    <Button type="button" variant="secondary" onClick={markAllRead} busy={markAllForm.processing} disabled={totalUnread === 0 || markAllForm.processing}>
                        {!markAllForm.processing && <Icon name="checkCircle" size={17} />}
                        {markAllForm.processing ? 'Marking all…' : 'Mark all as read'}
                    </Button>
                )}
            />

            <Card className="overflow-hidden">
                <div className="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <p className="text-sm text-slate-600"><span className="font-semibold text-slate-900">{formatSafeNumber(totalUnread)}</span> unread notifications</p>
                    <p className="text-xs text-slate-500">Newest first</p>
                </div>

                {rows.length ? (
                    <ul className="divide-y divide-slate-100" aria-label="Notifications">
                        {rows.map((notification) => {
                            const unread = isUnread(firstDefined(notification.read_at, notification.is_read));
                            const link = safeInternalUrl(firstDefined(notification.link, notification.url, notification.action_url));
                            return (
                                <li key={notification.id} className={`relative flex items-start gap-4 px-4 py-4 sm:px-5 ${unread ? 'bg-teal-50/35' : 'bg-white'}`}>
                                    {unread && <span className="absolute inset-y-3 left-0 w-1 rounded-r bg-teal-600" aria-label="Unread notification" />}
                                    <span className={`grid size-10 shrink-0 place-items-center rounded-xl ${unread ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-600'}`}>
                                        <Icon name={notificationIcon(notification.type)} size={20} />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className={`text-sm ${unread ? 'font-semibold text-slate-950' : 'font-medium text-slate-700'}`}>{notification.title || titleCase(notification.type) || 'Inventory update'}</h2>
                                            <Badge tone={notificationTone(notification.type)}>{titleCase(notification.type || 'system')}</Badge>
                                        </div>
                                        <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{notification.message || notification.body || 'Open this notification for details.'}</p>
                                        <p className="mt-1.5 text-xs text-slate-500" title={notification.created_at}>{notification.timeago || timeAgo(notification.created_at)}{!notification.timeago && notification.created_at ? ` · ${formatDateTime(notification.created_at)}` : ''}</p>
                                    </div>
                                    <div className="flex shrink-0 flex-col items-end gap-2">
                                        {unread && (
                                            <Button type="button" variant="ghost" size="sm" onClick={() => markRead(notification)} busy={markForm.processing && String(markForm.data.id) === String(notification.id)}>
                                                Mark read
                                            </Button>
                                        )}
                                        {link && (
                                            <Link href={link} className="btn btn-ghost btn-sm">
                                                Open <Icon name="external" size={15} /><span className="sr-only"> related record</span>
                                            </Link>
                                        )}
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                ) : (
                    <EmptyState
                        icon="bell"
                        title="No notifications yet"
                        description="New low-stock, expiry, and stock movement alerts will appear here."
                    />
                )}

                <Pagination meta={meta} />
            </Card>

            {markForm.errors.general && <p className="mt-4 text-sm font-medium text-red-700" role="alert">{markForm.errors.general}</p>}
            {markAllForm.errors.general && <p className="mt-4 text-sm font-medium text-red-700" role="alert">{markAllForm.errors.general}</p>}
        </>
    );
}

function formatSafeNumber(value) {
    return new Intl.NumberFormat().format(Number(value) || 0);
}
