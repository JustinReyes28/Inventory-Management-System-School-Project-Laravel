import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Icon from '../Components/Icons';
import { Notice } from '../Components/UI';
import { asArray, displayName, firstDefined, isUnread, safeInternalUrl, timeAgo, titleCase } from '../Utils';
import useInertiaLoading from '../Hooks/useInertiaLoading';
import { notificationsReadAllPath, paths } from '../Utils/routes';

const navigation = [
    { label: 'Overview', href: paths.dashboard, icon: 'dashboard', match: /^\/dashboard\/?$/ },
    { label: 'Items', href: paths.items, icon: 'box', match: /^\/items(?:\/|$)/, permission: 'view items' },
    { label: 'Categories', href: paths.categories, icon: 'layers', match: /^\/categories(?:\/|$)/, permission: 'view categories' },
    { label: 'Batches', href: paths.batches, icon: 'calendar', match: /^\/batches(?:\/|$)/, permission: 'view batches' },
    { label: 'Activity logs', href: paths.activityLogs, icon: 'history', match: /^\/activity-logs(?:\/|$)/, permission: 'view activity logs' },
    { label: 'Reports', href: paths.reports, icon: 'chart', match: /^\/reports(?:\/|$)/, permission: 'view reports' },
    { label: 'Notifications', href: paths.notifications, icon: 'bell', match: /^\/notifications(?:\/|$)/, permission: 'view notifications' },
    { label: 'Users', href: paths.users, icon: 'users', match: /^\/users(?:\/|$)/, permission: 'view users' },
];

function activeTitle(url) {
    const match = navigation.find((item) => item.match.test(url));
    return match?.label || 'Inventory workspace';
}

export default function AppLayout({ children }) {
    const page = usePage();
    const loading = useInertiaLoading();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [notificationOpen, setNotificationOpen] = useState(false);
    const notificationRef = useRef(null);
    const mobilePanelRef = useRef(null);
    const previousPathnameRef = useRef(null);
    const logoutForm = useForm({});
    const markAllForm = useForm({});

    const user = page.props.auth?.user || page.props.user || null;
    // Spatie permissions shared by HandleInertiaRequests drive UI visibility.
    const permissions = asArray(page.props.auth?.permissions);
    const can = (permission) => permissions.includes(permission);
    const pathname = page.url.split('?')[0];
    const title = activeTitle(pathname);
    const source = page.props.notificationSummary
        || page.props.notifications
        || page.props.auth?.notificationSummary
        || page.props.auth?.notifications
        || {};
    const recent = asArray(source.recent || source.items || source.data).slice(0, 3);
    const unreadCount = Number(firstDefined(
        page.props.notificationSummary?.unread_count,
        page.props.notificationSummary?.unreadCount,
        page.props.unreadCount,
        page.props.unread_count,
        source.unread_count,
        source.unreadCount,
        0,
    )) || 0;
    const flash = page.props.flash || {};
    const errorMessage = page.props.errors?.general || page.props.errors?.message;

    useEffect(() => {
        setMobileOpen(false);
        setNotificationOpen(false);
        if (previousPathnameRef.current && previousPathnameRef.current !== pathname) {
            window.requestAnimationFrame(() => document.getElementById('main-content')?.focus());
        }
        previousPathnameRef.current = pathname;
    }, [page.url, pathname]);

    useEffect(() => {
        if (!mobileOpen) return undefined;
        const previousFocus = document.activeElement;
        const original = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const focusTimer = window.setTimeout(() => {
            mobilePanelRef.current?.querySelector('a[href], button:not([disabled])')?.focus();
        }, 20);
        const handleDrawerKeys = (event) => {
            if (event.key === 'Escape') {
                setMobileOpen(false);
                return;
            }
            if (event.key !== 'Tab' || !mobilePanelRef.current) return;
            const focusable = [...mobilePanelRef.current.querySelectorAll('a[href], button:not([disabled])')];
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };
        document.addEventListener('keydown', handleDrawerKeys);
        return () => {
            window.clearTimeout(focusTimer);
            document.body.style.overflow = original;
            document.removeEventListener('keydown', handleDrawerKeys);
            if (previousFocus instanceof HTMLElement) previousFocus.focus();
        };
    }, [mobileOpen]);

    useEffect(() => {
        if (!notificationOpen) return undefined;
        function handleOutside(event) {
            if (!notificationRef.current?.contains(event.target)) setNotificationOpen(false);
        }
        function handleEscape(event) {
            if (event.key === 'Escape') setNotificationOpen(false);
        }
        document.addEventListener('mousedown', handleOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [notificationOpen]);

    const sidebar = (
        <div className="flex h-full flex-col bg-slate-950 text-white">
            <div className="flex h-17 items-center gap-3 border-b border-white/10 px-4">
                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-700 text-white shadow-lg shadow-teal-950/30">
                    <Icon name="layers" size={22} />
                </span>
                <div className="min-w-0">
                    <p className="truncate text-base font-bold tracking-tight">InvControl</p>
                    <p className="font-mono text-[10px] uppercase tracking-[0.18em] text-teal-300">Stock operations</p>
                </div>
                <button type="button" className="icon-button ml-auto text-slate-300 hover:!border-white/10 hover:!bg-white/10 hover:!text-white lg:hidden" onClick={() => setMobileOpen(false)} aria-label="Close navigation">
                    <Icon name="close" size={20} />
                </button>
            </div>

            {user && (
                <div className="border-b border-white/10 px-4 py-4">
                    <div className="flex items-center gap-3 rounded-xl bg-white/[0.06] p-3">
                        <span className="grid size-9 shrink-0 place-items-center rounded-full bg-teal-600 text-sm font-bold text-white" aria-hidden="true">
                            {displayName(user).charAt(0).toUpperCase()}
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-white">{displayName(user)}</p>
                            <p className="truncate text-xs text-slate-400">{firstDefined(user.email, user.username, 'Team account')}</p>
                        </div>
                    </div>
                </div>
            )}

            <nav className="flex-1 overflow-y-auto px-3 py-4" aria-label="Primary navigation">
                <p className="mb-2 px-3 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace</p>
                <ul className="space-y-1">
                    {navigation.filter((item) => !item.permission || can(item.permission)).map((item) => {
                        const active = item.match.test(pathname);
                        return (
                            <li key={item.href}>
                                <Link href={item.href} className={`nav-link ${active ? 'nav-link-active' : ''}`} aria-current={active ? 'page' : undefined}>
                                    <Icon name={item.icon} size={19} className="shrink-0" />
                                    <span>{item.label}</span>
                                    {item.href === paths.notifications && unreadCount > 0 && (
                                        <span className="ml-auto min-w-5 rounded-full bg-amber-300 px-1.5 py-0.5 text-center font-mono text-[10px] font-bold text-amber-950">
                                            {unreadCount > 99 ? '99+' : unreadCount}
                                        </span>
                                    )}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <div className="border-t border-white/10 p-3">
                <Link
                    href={paths.account}
                    className="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                >
                    <Icon name="key" size={19} />
                    <span>My account</span>
                </Link>
                <button
                    type="button"
                    className="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white"
                    onClick={() => logoutForm.post(paths.logout)}
                    disabled={logoutForm.processing}
                >
                    {logoutForm.processing ? <span className="spinner" aria-hidden="true" /> : <Icon name="logout" size={19} />}
                    <span>{logoutForm.processing ? 'Signing out…' : 'Sign out'}</span>
                </button>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50">
            <a className="skip-link" href="#main-content">Skip to main content</a>
            <div className={`fixed inset-x-0 top-0 z-[90] h-0.5 origin-left bg-teal-600 transition-transform ${loading ? 'scale-x-75 opacity-100' : 'scale-x-0 opacity-0'}`} role="progressbar" aria-label="Loading page" aria-hidden={!loading} />

            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 lg:block" aria-label="Application sidebar">{sidebar}</aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <button type="button" className="absolute inset-0 bg-slate-950/65" onClick={() => setMobileOpen(false)} aria-label="Close navigation overlay" />
                    <aside ref={mobilePanelRef} className="relative h-full w-[min(19rem,88vw)] shadow-2xl" aria-label="Application sidebar">{sidebar}</aside>
                </div>
            )}

            <div className="lg:pl-64">
                <header className="sticky top-0 z-30 flex h-17 items-center border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8 no-print">
                    <button type="button" className="icon-button mr-2 lg:hidden" onClick={() => setMobileOpen(true)} aria-label="Open navigation" aria-expanded={mobileOpen}>
                        <Icon name="menu" size={22} />
                    </button>
                    <div className="min-w-0">
                        <p className="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Inventory / {String(page.url).split('?')[0].split('/').filter(Boolean).pop() || 'dashboard'}</p>
                        <p className="truncate text-sm font-semibold text-slate-900">{title}</p>
                    </div>

                    <div className="ml-auto flex items-center gap-1.5 sm:gap-3">
                        <div className="relative" ref={notificationRef}>
                            <button
                                type="button"
                                className="icon-button relative"
                                onClick={() => setNotificationOpen((open) => !open)}
                                aria-label={`${unreadCount} unread notifications`}
                                aria-haspopup="true"
                                aria-expanded={notificationOpen}
                                aria-controls="notification-summary"
                            >
                                <Icon name="bell" size={20} />
                                {unreadCount > 0 && <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-amber-500 ring-2 ring-white" aria-hidden="true" />}
                            </button>
                            <span className="sr-only" aria-live="polite">{unreadCount} unread notifications</span>

                            {notificationOpen && (
                                <div id="notification-summary" className="absolute right-0 top-12 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-float" role="dialog" aria-label="Notification summary">
                                    <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">Notifications</p>
                                            <p className="text-xs text-slate-500">{unreadCount} unread</p>
                                        </div>
                                        {unreadCount > 0 && (
                                            <button
                                                type="button"
                                                className="min-h-11 rounded-lg px-2 text-xs font-semibold text-teal-700 hover:bg-teal-50 hover:text-teal-900"
                                                onClick={() => markAllForm.post(notificationsReadAllPath, { preserveScroll: true })}
                                                disabled={markAllForm.processing}
                                            >
                                                {markAllForm.processing ? 'Marking…' : 'Mark all read'}
                                            </button>
                                        )}
                                    </div>
                                    <div className="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                                        {recent.length ? recent.map((item) => {
                                            const itemLink = safeInternalUrl(item.link || item.url);
                                            const content = (
                                                <>
                                                    <div className="min-w-0 flex-1">
                                                        <p className={`truncate text-sm ${isUnread(item.read_at ?? item.is_read) ? 'font-semibold text-slate-900' : 'text-slate-600'}`}>{item.title || 'Inventory update'}</p>
                                                        <p className="mt-0.5 line-clamp-2 text-xs leading-5 text-slate-500">{item.message || item.body || 'Open notifications for details.'}</p>
                                                        <p className="mt-1 text-xs text-slate-500">{item.timeago || timeAgo(item.created_at)}</p>
                                                    </div>
                                                    {isUnread(item.read_at ?? item.is_read) && <span className="mt-2 size-2 shrink-0 rounded-full bg-teal-600" aria-label="Unread" />}
                                                </>
                                            );
                                            return itemLink ? (
                                                <Link key={item.id} href={itemLink} className="flex items-start gap-3 px-4 py-3 hover:bg-slate-50">{content}</Link>
                                            ) : (
                                                <div key={item.id} className="flex items-start gap-3 px-4 py-3">{content}</div>
                                            );
                                        }) : (
                                            <div className="px-5 py-8 text-center">
                                                <Icon name="checkCircle" size={24} className="mx-auto text-teal-700" />
                                                <p className="mt-2 text-sm font-semibold text-slate-800">You’re caught up</p>
                                                <p className="mt-1 text-xs text-slate-500">No recent inventory alerts.</p>
                                            </div>
                                        )}
                                    </div>
                                    <Link href={paths.notifications} className="flex min-h-11 items-center justify-center gap-2 border-t border-slate-200 bg-slate-50 text-sm font-semibold text-teal-800 hover:bg-teal-50">
                                        View all notifications <Icon name="chevronRight" size={16} />
                                    </Link>
                                </div>
                            )}
                        </div>

                        {user && (
                            <div className="hidden items-center gap-2 border-l border-slate-200 pl-3 sm:flex">
                                <span className="grid size-9 place-items-center rounded-full bg-teal-100 text-sm font-bold text-teal-900" aria-hidden="true">{displayName(user).charAt(0).toUpperCase()}</span>
                                <div className="max-w-36">
                                    <p className="truncate text-sm font-semibold text-slate-900">{displayName(user)}</p>
                                    <p className="truncate text-xs text-slate-500">{titleCase(user.role?.name || user.role_name || user.role || 'Team member')}</p>
                                </div>
                            </div>
                        )}
                    </div>
                </header>

                <main id="main-content" className="mx-auto w-full max-w-[100rem] p-4 sm:p-6 lg:p-8" tabIndex="-1">
                    {(flash.success || flash.message) && (
                        <Notice tone="success" className="mb-5" title="Change saved">
                            {flash.message && flash.message !== flash.success ? flash.message : 'Your inventory workspace has been updated.'}
                        </Notice>
                    )}
                    {(flash.error || errorMessage) && <Notice tone="error" className="mb-5" title="Something went wrong">{flash.error || errorMessage}</Notice>}
                    {children}
                </main>
            </div>
        </div>
    );
}
