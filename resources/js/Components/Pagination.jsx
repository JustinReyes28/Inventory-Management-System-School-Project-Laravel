import { Link } from '@inertiajs/react';
import Icon from './Icons';

export default function Pagination({ meta, currentQuery = {} }) {
    if (!meta || meta.lastPage <= 1) return null;

    const pageUrl = (page) => {
        const parsed = new URL(window.location.href);
        Object.entries(currentQuery).forEach(([key, value]) => {
            if (value === '' || value === null || value === undefined) parsed.searchParams.delete(key);
            else parsed.searchParams.set(key, String(value));
        });
        parsed.searchParams.set('page', String(page));
        return `${parsed.pathname}${parsed.search}`;
    };

    const visible = [];
    const start = Math.max(1, meta.currentPage - 2);
    const end = Math.min(meta.lastPage, start + 4);
    for (let page = Math.max(1, end - 4); page <= end; page += 1) visible.push(page);

    return (
        <nav className="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row" aria-label="Pagination">
            <p className="text-sm text-slate-600" aria-live="polite">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total ?? 0}
            </p>
            <div className="flex flex-wrap items-center justify-center gap-1">
                {meta.currentPage > 1 && (
                    <Link href={pageUrl(meta.currentPage - 1)} preserveScroll className="pagination-button" aria-label="Previous page">
                        <Icon name="chevronLeft" size={17} /><span className="hidden sm:inline">Previous</span>
                    </Link>
                )}
                {visible[0] > 1 && <span className="px-1 text-slate-400" aria-hidden="true">…</span>}
                {visible.map((page) => (
                    <Link
                        key={page}
                        href={pageUrl(page)}
                        preserveScroll
                        className={`pagination-button ${page === meta.currentPage ? 'pagination-button-active' : ''}`}
                        aria-current={page === meta.currentPage ? 'page' : undefined}
                        aria-label={`Page ${page}`}
                    >
                        {page}
                    </Link>
                ))}
                {visible.at(-1) < meta.lastPage && <span className="px-1 text-slate-400" aria-hidden="true">…</span>}
                {meta.currentPage < meta.lastPage && (
                    <Link href={pageUrl(meta.currentPage + 1)} preserveScroll className="pagination-button" aria-label="Next page">
                        <span className="hidden sm:inline">Next</span><Icon name="chevronRight" size={17} />
                    </Link>
                )}
            </div>
        </nav>
    );
}
