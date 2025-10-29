import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

const isDevelopment = process.env.NODE_ENV === 'local';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/styles.css',
            'resources/js/app.ts',
            'resources/js/vendor/livewire-data-tables.js',
            'resources/js/vendor/livewire.js',
        ]),
        tailwindcss(),
        react(),
    ],
    optimizeDeps: {
        esbuildOptions: {
            tsconfig: 'tsconfig.json',
        },
    },
    resolve: {
        alias: {
            configuration: path.resolve(__dirname, 'resources/js/configuration'),
            'event-system': path.resolve(__dirname, 'resources/js/event-system'),
            'api-handler': path.resolve(__dirname, 'resources/js/api-handler'),
            'game-data': path.resolve(__dirname, 'resources/js/game-data'),
            'game-utils': path.resolve(__dirname, 'resources/js/game/util'),
            components: path.resolve(__dirname, 'resources/js/components'),
            ui: path.resolve(__dirname, 'resources/js/ui'),
            'service-container': path.resolve(__dirname, 'resources/js/service-container'),
            'service-container-provider': path.resolve(__dirname, 'resources/js/service-container-provider'),
            'screen-manager': path.resolve(__dirname, 'resources/js/screen-manager'),
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
