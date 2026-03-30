import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// Vitest starts Vite for tests, not the Laravel dev/HMR server. laravel-vite-plugin
// blocks CI otherwise; bypass only for the Vitest process.
if (process.env.VITEST === 'true') {
    process.env.LARAVEL_BYPASS_ENV_CHECK = '1';
}

export default defineConfig({
    test: {
        globals: true,
        environment: 'jsdom',
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/js/draft-preview.ts', 'resources/css/app.css'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
