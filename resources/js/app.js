import './bootstrap';
import '../css/app.css';
import 'preline/preline';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// O Axios usado internamente pelo Inertia não é necessariamente a mesma
// instância exposta em window. Envie o token explicitamente para que POSTs,
// PUTs, PATCHes e DELETEs continuem funcionando após a mudança para HTTPS.
router.on('before', (event) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (csrfToken && event.detail?.visit?.headers) {
        event.detail.visit.headers['X-CSRF-TOKEN'] = csrfToken;
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h(App, props),
            mounted() {}
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        // Inicializar componentes ou plugins adicionais, se necessário
        if (typeof HSStaticMethods !== 'undefined') {
            HSStaticMethods.autoInit();
        }

        return app;
    },
    progress: {
        color: '#005eff',
    },
});
