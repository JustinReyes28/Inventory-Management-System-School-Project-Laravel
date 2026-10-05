import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import AppLayout from './Layouts/AppLayout';
import './bootstrap';
import '../css/app.css';

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    resolve: (name) => {
        const loadPage = pages[`./Pages/${name}.jsx`];
        if (!loadPage) throw new Error(`Inertia page not found: ${name}`);
        const page = loadPage();
        if (name === 'Auth/Login') return page;
        return page.then((module) => ({
            ...module,
            layout: ({ children }) => <AppLayout>{children}</AppLayout>,
        }));
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#0f766e',
        showSpinner: false,
    },
});
