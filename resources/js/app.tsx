import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { setNonce } from 'get-nonce';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

// Dialogs lock page scrolling with a small injected style tag; give it the page's CSP nonce.
const nonce = document.querySelector<HTMLMetaElement>('meta[name="csp-nonce"]')?.content;
if (nonce) {
    setNonce(nonce);
}

const appName = import.meta.env.VITE_APP_NAME || 'Second Half';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        // The bar is styled in app.css so no inline <style> is injected.
        includeCSS: false,
        showSpinner: false,
    },
});

initializeTheme();
