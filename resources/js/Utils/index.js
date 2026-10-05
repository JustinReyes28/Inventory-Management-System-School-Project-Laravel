export function asArray(value) {
    if (Array.isArray(value)) return value;
    if (Array.isArray(value?.data)) return value.data;
    if (Array.isArray(value?.items)) return value.items;
    if (Array.isArray(value?.results)) return value.results;
    return [];
}

export function normalizePagination(resource, fallbackPage = 1) {
    if (Array.isArray(resource)) {
        return {
            currentPage: fallbackPage,
            lastPage: 1,
            from: resource.length ? 1 : null,
            to: resource.length,
            total: resource.length,
            perPage: resource.length || 15,
            links: [],
        };
    }

    const source = resource?.pagination || resource?.meta || resource || {};
    const currentPage = Number(source.current_page ?? source.currentPage ?? source.page ?? fallbackPage) || fallbackPage;
    const lastPage = Number(source.last_page ?? source.lastPage ?? source.total_pages ?? source.totalPages ?? 1) || 1;
    const perPage = Number(source.per_page ?? source.perPage ?? 15) || 15;
    const total = Number(source.total ?? 0) || 0;

    return {
        currentPage,
        lastPage,
        from: source.from ?? (total ? (currentPage - 1) * perPage + 1 : null),
        to: source.to ?? Math.min(currentPage * perPage, total),
        total,
        perPage,
        links: Array.isArray(source.links) ? source.links : [],
    };
}

export function firstDefined(...values) {
    return values.find((value) => value !== undefined && value !== null);
}

export function numberValue(...values) {
    const value = Number(firstDefined(values, 0));
    return Number.isFinite(value) ? value : 0;
}

export function stringValue(...values) {
    const value = firstDefined(values, '');
    return value === null || value === undefined ? '' : String(value);
}

export function displayName(record, fallback = 'Unknown') {
    if (!record) return fallback;
    const value = firstDefined(record.name, record.full_name, record.user_name, record.username, record.email);
    return value ? String(value) : fallback;
}

export function relationName(record, keys = ['name'], fallback = '—') {
    for (const key of keys) {
        const relation = record?.[key];
        if (typeof relation === 'string') return relation;
        if (relation && typeof relation === 'object') return displayName(relation, fallback);
    }
    const direct = keys.map((key) => record?.[key]).find(Boolean);
    return direct ? String(direct) : fallback;
}

export function titleCase(value) {
    return String(value ?? '')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function formatCurrency(value, currency = 'USD') {
    const amount = Number(value);
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function formatNumber(value) {
    return new Intl.NumberFormat().format(numberValue(value));
}

export function parseDate(value) {
    if (!value) return null;
    const normalized = String(value).includes('T') ? String(value) : String(value).replace(' ', 'T');
    const date = new Date(normalized);
    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(value, options = {}) {
    const date = parseDate(value);
    if (!date) return '—';
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...options,
    }).format(date);
}

export function formatDateTime(value) {
    return formatDate(value, { hour: 'numeric', minute: '2-digit' });
}

export function timeAgo(value) {
    const date = parseDate(value);
    if (!date) return '—';
    const seconds = Math.round((date.getTime() - Date.now()) / 1000);
    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
    const ranges = [
        ['year', 31_536_000],
        ['month', 2_592_000],
        ['week', 604_800],
        ['day', 86_400],
        ['hour', 3_600],
        ['minute', 60],
    ];
    for (const [unit, divisor] of ranges) {
        if (Math.abs(seconds) >= divisor) return formatter.format(Math.round(seconds / divisor), unit);
    }
    return 'Just now';
}

export function expiryDays(expiryDate) {
    const date = parseDate(expiryDate);
    if (!date) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    date.setHours(0, 0, 0, 0);
    return Math.ceil((date.getTime() - today.getTime()) / 86_400_000);
}

export function isUnread(value) {
    return value === false || value === 0 || value === '0' || value === 'false' || value === undefined;
}

export function safeInternalUrl(value) {
    if (!value || typeof value !== 'string') return null;
    const url = value.trim();
    if (!url.startsWith('/') || url.startsWith('//') || url.includes('\\')) return null;
    return url;
}

export function getErrors(errors, field) {
    const value = errors?.[field];
    return Array.isArray(value) ? value[0] : value;
}

export function classNames(...values) {
    return values.filter(Boolean).join(' ');
}

export function normalizeQuery(value) {
    if (typeof value !== 'string') return '';
    try {
        return new URLSearchParams(value).toString();
    } catch {
        return '';
    }
}
