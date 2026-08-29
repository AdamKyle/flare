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
            'resources/js/game.ts',
            'resources/js/admin-apps.ts',
            'resources/js/layouts/app-layout.ts',
            'resources/js/admin/monitoring/batch-crafting-monitoring.tsx',
            'resources/js/admin/monitoring/battle-reward-queue.tsx',
            'resources/js/admin/monitoring/delve-monitoring.tsx',
            'resources/js/admin/monitoring/exploration-monitoring.tsx',
            'resources/js/admin/monitoring/faction-loyalty-monitoring.tsx',
            'resources/js/admin/monitoring/logs-dashboard.tsx',
            'resources/js/admin/game-maps/game-maps-app.tsx',
            'resources/js/admin/locations/locations-app.tsx',
            'resources/js/admin/npcs/npcs-app.tsx',
            'resources/js/admin/items/items-app.tsx',
        ]),
        tailwindcss(),
        react(),
    ],
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
            websockets: path.resolve(__dirname, 'resources/js/websocket-handler'),
        },
    },
    build: {
        minify: !isDevelopment,
        sourcemap: isDevelopment,
        chunkSizeWarningLimit: 2000,
        rolldownOptions: {
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
