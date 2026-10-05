import { useEffect, useRef } from 'react';
import Icon from './Icons';

export default function Modal({ open, onClose, title, description, children, footer, size = 'md' }) {
    const panelRef = useRef(null);
    const previousFocusRef = useRef(null);
    const onCloseRef = useRef(onClose);

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

    useEffect(() => {
        if (!open) return undefined;

        previousFocusRef.current = document.activeElement;
        const originalOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const focusTimer = window.setTimeout(() => {
            const formControl = panelRef.current?.querySelector('form input:not([type="hidden"]), form select, form textarea');
            const firstControl = formControl || panelRef.current?.querySelector('input:not([type="hidden"]), select, textarea, button');
            (firstControl || panelRef.current)?.focus();
        }, 20);

        const handleKeyDown = (event) => {
            if (event.key === 'Escape' && !event.defaultPrevented) onCloseRef.current();
            if (event.key !== 'Tab' || !panelRef.current) return;

            const focusable = [...panelRef.current.querySelectorAll(
                'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )].filter((element) => !element.hasAttribute('hidden'));
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

        document.addEventListener('keydown', handleKeyDown);
        return () => {
            window.clearTimeout(focusTimer);
            document.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = originalOverflow;
            if (previousFocusRef.current instanceof HTMLElement) previousFocusRef.current.focus();
        };
    }, [open]);

    if (!open) return null;

    const widths = { sm: 'max-w-md', md: 'max-w-2xl', lg: 'max-w-4xl', xl: 'max-w-5xl' };

    return (
        <div className="modal-backdrop" role="presentation" onMouseDown={(event) => {
            if (event.target === event.currentTarget) onClose();
        }}>
            <section
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
                aria-describedby={description ? 'modal-description' : undefined}
                tabIndex="-1"
                className={`modal-panel ${widths[size] || widths.md}`}
            >
                <header className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <h2 id="modal-title" className="text-lg font-semibold tracking-tight text-slate-950">{title}</h2>
                        {description && <p id="modal-description" className="mt-1 text-sm text-slate-600">{description}</p>}
                    </div>
                    <button type="button" className="icon-button" onClick={onClose} aria-label={`Close ${title.toLowerCase()}`}>
                        <Icon name="close" size={20} />
                    </button>
                </header>
                <div className="max-h-[calc(100vh-12rem)] overflow-y-auto px-5 py-5 sm:px-6">{children}</div>
                {footer && <footer className="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">{footer}</footer>}
            </section>
        </div>
    );
}

export function ConfirmDialog({ open, onClose, onConfirm, title, message, confirmLabel = 'Delete', busy = false, tone = 'danger' }) {
    return (
        <Modal
            open={open}
            onClose={onClose}
            title={title}
            size="sm"
            footer={(
                <>
                    <button type="button" className="btn btn-secondary" onClick={onClose} disabled={busy}>Cancel</button>
                    <button type="button" className={`btn ${tone === 'danger' ? 'btn-danger' : 'btn-primary'}`} onClick={onConfirm} disabled={busy}>
                        {busy && <span className="spinner" aria-hidden="true" />}
                        {busy ? 'Working…' : confirmLabel}
                    </button>
                </>
            )}
        >
            <p className="text-sm leading-6 text-slate-600">{message}</p>
        </Modal>
    );
}
