import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

// Determine if we are in development mode
const isDevelopment = process.env.NODE_ENV === 'local';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/tailwind.css',
            'resources/js/app.ts',
            'resources/js/admin-app.ts',
            'resources/js/shop-component.ts',
            'resources/js/player-event-calendar-component.ts',
            'resources/js/guide-quests-init.tsx',
            'resources/js/items-table-component.ts',
            'resources/js/online-character-stats-component.ts',
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
            '@mui': '/node_modules/@mui',
        },
    },
    build: {
        minify: !isDevelopment,
        sourcemap: isDevelopment,
        chunkSizeWarningLimit: 2000,
    },
});
