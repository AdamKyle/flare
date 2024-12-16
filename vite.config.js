import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

const isDevelopment = process.env.NODE_ENV === 'local';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/tailwind.css',
            'resources/js/app.ts',
            'resources/js/vendor/theme-script.js',
            'resources/vendor/theme/assets/js/dark-mode/dark-mode.js',
            'resources/js/vendor/livewire-data-tables.js',
            'resources/js/vendor/livewire.js',
        ]),
        react(),
    ],
    optimizeDeps: {
        esbuildOptions: {
            tsconfig: 'tsconfig.json'
        }
    },
    resolve: {
        alias: {
            configuration: path.resolve(__dirname, 'resources/js/configuration'),
            'event-system': path.resolve(__dirname, 'resources/js/event-system'),
            components: path.resolve(__dirname, 'resources/js/components'),
            ui: path.resolve(__dirname, 'resources/js/ui'),
            'service-container': path.resolve(__dirname, 'resources/js/service-container'),
            'service-container-provider': path.resolve(__dirname, 'resources/js/service-container-provider'),
        },
    },
    build: {
        minify: !isDevelopment,
        sourcemap: isDevelopment,
        inlineDynamicImports: !isDevelopment,
        chunkSizeWarningLimit: 2000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    const match = id.match(/node_modules\/([^/]+)/);
                    if (match) {
                        const packageName = match[1];
                        if (id.includes('node_modules/')) {
                            return `vendor_${packageName}`;
                        }
                    }
                },
            },
        },
    },
});
