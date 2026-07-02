import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: (title) =>
        `${title} - ${import.meta.env.VITE_APP_NAME ?? 'MOA-MOU Tracking'}`,
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        const tryPaths = [
            `./pages/${name}.tsx`,
            `./pages/${name.charAt(0).toUpperCase() + name.slice(1)}.tsx`,
        ];

        for (const p of tryPaths) {
            try {
                return resolvePageComponent(p, pages) as any;
            } catch (e) {
                // try next
            }
        }

        return resolvePageComponent(`./pages/${name}.tsx`, pages) as any;
    },
    setup({ el, App, props }) {
        if (el) {
            createRoot(el).render(<App {...props} />);

            return;
        }

        return <App {...props} />;
    },
    progress: {
        color: '#4B5563',
    },
});
