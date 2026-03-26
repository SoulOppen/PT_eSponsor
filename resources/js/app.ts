import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h, type DefineComponent } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// @ts-ignore TS server may fallback to non-ES module mode in some IDE sessions.
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
// @ts-ignore TS server may fallback to non-ES module mode in some IDE sessions.
const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const importPage = pages[`./Pages/${name}.vue`];
        if (!importPage) {
            throw new Error(`Page not found: ${name}`);
        }
        return importPage().then((page) => page.default);
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
