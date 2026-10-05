import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import AppLayout from './Layouts/AppLayout';
import './bootstrap';
import '../css/app.css';

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    resolve: (name) =>
        // Lazy page loading through Vite's glob + resolvePageComponent.
        resolvePageComponent(`./Pages/${name}.jsx`, pages).then((module) => {
            // Guest screens under Auth/ render their own GuestLayout and must
            // not inherit the authenticated application navigation.
            if (name.startsWith('Auth/')) {
                return module;
            }

            return {
                ...module,
                layout: ({ children }) => <AppLayout>{children}</AppLayout>,
            };
        }),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#0f766e',
        showSpinner: false,
    },
});
