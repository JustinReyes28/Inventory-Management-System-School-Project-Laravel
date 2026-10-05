export const paths = {
    login: '/login',
    logout: '/logout',
    dashboard: '/dashboard',
    items: '/items',
    categories: '/categories',
    batches: '/batches',
    activityLogs: '/activity-logs',
    users: '/users',
    reports: '/reports',
    notifications: '/notifications',
};

export const resourcePath = (resource, id) => id ? `/${resource}/${encodeURIComponent(id)}` : `/${resource}`;
export const notificationReadPath = (id) => `/notifications/${encodeURIComponent(id)}/read`;
export const notificationsReadAllPath = '/notifications/read-all';
