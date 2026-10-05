import { Link } from '@inertiajs/react';

/**
 * Guest layout for the authentication screens (login, registration, password
 * recovery). Deliberately free of the authenticated application navigation.
 */
export default function GuestLayout({ children, title, subtitle, footer }) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <div className="w-full max-w-md">
                <div className="mb-6 text-center">
                    <Link href="/" className="font-mono text-xs font-semibold uppercase tracking-[0.22em] text-teal-800">
                        InvControl
                    </Link>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    {title && <h1 className="text-2xl font-bold text-slate-900">{title}</h1>}
                    {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
                    <div className={title ? 'mt-6' : ''}>{children}</div>
                </div>

                {footer && <div className="mt-4 text-center text-sm text-slate-600">{footer}</div>}
            </div>
        </div>
    );
}
