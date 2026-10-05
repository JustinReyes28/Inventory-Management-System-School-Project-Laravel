import Icon from './Icons';
import { classNames, firstDefined, numberValue, titleCase } from '../Utils';

export function Button({ variant = 'primary', size = 'md', busy = false, disabled = false, className = '', children, ...props }) {
    return (
        <button className={`btn btn-${variant} ${size === 'sm' ? 'btn-sm' : ''} ${className}`} disabled={busy || disabled} {...props}>
            {busy && <span className="spinner" aria-hidden="true" />}
            {children}
        </button>
    );
}

export function PageHeader({ eyebrow, title, description, actions }) {
    return (
        <header className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                {eyebrow && <p className="eyebrow">{eyebrow}</p>}
                <h1 className="page-title">{title}</h1>
                {description && <p className="mt-1.5 max-w-3xl text-sm leading-6 text-slate-600">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2 no-print">{actions}</div>}
        </header>
    );
}

export function Card({ children, className = '', as: Component = 'section' }) {
    return <Component className={`app-card ${className}`}>{children}</Component>;
}

export function CardHeader({ title, description, actions, id }) {
    return (
        <div className="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 id={id} className="text-base font-semibold text-slate-950">{title}</h2>
                {description && <p className="mt-1 text-sm text-slate-600">{description}</p>}
            </div>
            {actions && <div className="flex items-center gap-2 no-print">{actions}</div>}
        </div>
    );
}

export function MetricCard({ label, value, hint, icon = 'box', tone = 'teal' }) {
    const tones = {
        teal: 'bg-teal-50 text-teal-700 ring-teal-100',
        amber: 'bg-amber-50 text-amber-700 ring-amber-100',
        red: 'bg-red-50 text-red-700 ring-red-100',
        blue: 'bg-blue-50 text-blue-700 ring-blue-100',
    };
    return (
        <Card className="metric-card p-4 sm:p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-slate-600">{label}</p>
                    <p className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{value}</p>
                    {hint && <p className="mt-1.5 text-xs leading-5 text-slate-500">{hint}</p>}
                </div>
                <span className={`grid size-10 shrink-0 place-items-center rounded-xl ring-1 ${tones[tone] || tones.teal}`}>
                    <Icon name={icon} size={20} />
                </span>
            </div>
        </Card>
    );
}

export function LoadingState({ label = 'Loading data…', compact = false }) {
    return (
        <div className={`flex items-center justify-center gap-3 text-sm text-slate-600 ${compact ? 'py-6' : 'py-14'}`} role="status" aria-live="polite">
            <span className="spinner spinner-lg" aria-hidden="true" />
            <span>{label}</span>
        </div>
    );
}

export function EmptyState({ icon = 'inbox', title, description, action, compact = false }) {
    return (
        <div className={`flex flex-col items-center justify-center px-5 text-center ${compact ? 'py-10' : 'py-16'}`}>
            <span className="grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                <Icon name={icon} size={24} />
            </span>
            <h3 className="mt-4 text-base font-semibold text-slate-900">{title}</h3>
            {description && <p className="mt-1.5 max-w-md text-sm leading-6 text-slate-600">{description}</p>}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}

export function ErrorState({ title = 'We could not load this data', message, onRetry }) {
    return (
        <div className="flex flex-col items-center justify-center px-5 py-14 text-center" role="alert">
            <span className="grid size-12 place-items-center rounded-2xl bg-red-50 text-red-700"><Icon name="warning" size={24} /></span>
            <h3 className="mt-4 text-base font-semibold text-slate-900">{title}</h3>
            <p className="mt-1.5 max-w-md text-sm leading-6 text-slate-600">{message || 'Check your connection and try again.'}</p>
            {onRetry && <Button type="button" variant="secondary" className="mt-5" onClick={onRetry}><Icon name="refresh" size={17} />Try again</Button>}
        </div>
    );
}

export function Notice({ tone = 'info', title, children, className = '' }) {
    const tones = {
        info: 'border-blue-200 bg-blue-50 text-blue-900',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        warning: 'border-amber-200 bg-amber-50 text-amber-950',
        error: 'border-red-200 bg-red-50 text-red-900',
    };
    const icons = { info: 'info', success: 'checkCircle', warning: 'warning', error: 'xCircle' };
    return (
        <div className={`flex items-start gap-3 rounded-xl border p-4 text-sm ${tones[tone]} ${className}`} role={tone === 'error' ? 'alert' : 'status'}>
            <Icon name={icons[tone]} size={19} className="mt-0.5 shrink-0" />
            <div className="min-w-0">
                {title && <p className="font-semibold">{title}</p>}
                {children && <div className={title ? 'mt-1 leading-6' : 'leading-6'}>{children}</div>}
            </div>
        </div>
    );
}

export function FilterBar({ children, onReset, resetLabel = 'Reset filters' }) {
    return (
        <div className="filter-bar no-print">
            <div className="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">{children}</div>
            {onReset && <Button type="button" variant="ghost" onClick={onReset}>{resetLabel}</Button>}
        </div>
    );
}

export function SearchField({ label = 'Search', value, onChange, placeholder, className = '' }) {
    return (
        <label className={`form-group ${className}`}>
            <span className="form-label">{label}</span>
            <span className="relative block">
                <Icon name="search" size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input className="form-input pl-10" type="search" value={value} onChange={(event) => onChange(event.target.value)} placeholder={placeholder} />
            </span>
        </label>
    );
}

export function Badge({ children, tone = 'slate', dot = false, className = '' }) {
    const tones = {
        slate: 'bg-slate-100 text-slate-700 ring-slate-200',
        teal: 'bg-teal-50 text-teal-800 ring-teal-200',
        green: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        amber: 'bg-amber-50 text-amber-900 ring-amber-200',
        red: 'bg-red-50 text-red-800 ring-red-200',
        blue: 'bg-blue-50 text-blue-800 ring-blue-200',
        purple: 'bg-violet-50 text-violet-800 ring-violet-200',
    };
    return <span className={`badge ${tones[tone] || tones.slate} ${className}`}>{dot && <span className="size-1.5 rounded-full bg-current" aria-hidden="true" />}{children}</span>;
}

export function StockBadge({ quantity, threshold }) {
    const current = numberValue(quantity);
    const limit = numberValue(threshold);
    if (current <= 0) return <Badge tone="red" dot>Out of stock</Badge>;
    if (current <= limit) return <Badge tone="amber" dot>Low stock</Badge>;
    return <Badge tone="green" dot>In stock</Badge>;
}

export function ActionBadge({ action }) {
    const key = String(action || 'system').toLowerCase();
    const map = {
        create: 'teal', update: 'blue', delete: 'red', adjustment: 'blue',
        stock_in: 'green', stock_out: 'amber', login: 'purple', logout: 'slate', system: 'slate',
    };
    return <Badge tone={map[key] || 'slate'}>{titleCase(key)}</Badge>;
}

export function ExpiryBadge({ date, status, days }) {
    const explicitDays = firstDefined(days, date);
    const dayCount = Number(explicitDays);
    let tone = 'green';
    let label = status || 'Safe';
    if (status && !Number.isFinite(dayCount)) {
        tone = /expired/i.test(status) ? 'red' : /critical/i.test(status) ? 'red' : /warning/i.test(status) ? 'amber' : /notice/i.test(status) ? 'blue' : 'green';
        label = titleCase(status);
    } else if (Number.isFinite(dayCount)) {
        if (dayCount < 0) { tone = 'red'; label = `${Math.abs(dayCount)}d expired`; }
        else if (dayCount <= 7) { tone = 'red'; label = `${dayCount}d left`; }
        else if (dayCount <= 30) { tone = 'amber'; label = `${dayCount}d left`; }
        else if (dayCount <= 60) { tone = 'blue'; label = `${dayCount}d left`; }
        else { tone = 'green'; label = `${dayCount}d left`; }
    }
    return <Badge tone={tone} dot>{label}</Badge>;
}

export function TableState({ colSpan, loading, error, empty, emptyTitle, emptyDescription, emptyIcon, onRetry }) {
    if (loading) return <tr><td colSpan={colSpan}><LoadingState compact /></td></tr>;
    if (error) return <tr><td colSpan={colSpan}><ErrorState message={error} onRetry={onRetry} /></td></tr>;
    if (empty) return <tr><td colSpan={colSpan}><EmptyState compact icon={emptyIcon} title={emptyTitle} description={emptyDescription} /></td></tr>;
    return null;
}

export function Tabs({ tabs, active, onChange, label = 'Report views' }) {
    function handleKeyDown(event, index) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        let next = index;
        if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
        if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = tabs.length - 1;
        onChange(tabs[next].value);
        document.getElementById(`tab-${tabs[next].value}`)?.focus();
    }

    return (
        <div className="overflow-x-auto border-b border-slate-200 no-print" role="tablist" aria-label={label}>
            <div className="flex min-w-max gap-1 px-3 pt-2">
                {tabs.map((tab, index) => (
                    <button
                        key={tab.value}
                        id={`tab-${tab.value}`}
                        type="button"
                        role="tab"
                        aria-selected={active === tab.value}
                        aria-controls={`panel-${tab.value}`}
                        tabIndex={active === tab.value ? 0 : -1}
                        className={`tab-button ${active === tab.value ? 'tab-button-active' : ''}`}
                        onClick={() => onChange(tab.value)}
                        onKeyDown={(event) => handleKeyDown(event, index)}
                    >
                        {tab.icon && <Icon name={tab.icon} size={17} />}{tab.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

export function Checkbox({ label, checked, onChange, disabled = false, ...props }) {
    return (
        <label className={classNames('flex min-h-11 cursor-pointer items-center gap-3 text-sm font-medium text-slate-700', disabled && 'cursor-not-allowed opacity-60')}>
            <input type="checkbox" className="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600" checked={checked} onChange={onChange} disabled={disabled} {...props} />
            <span>{label}</span>
        </label>
    );
}
