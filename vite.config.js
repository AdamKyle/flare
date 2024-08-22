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
        inlineDynamicImports: !isDevelopment,
        terserOptions: {
            compress: {
                drop_console: !isDevelopment,
            },
        },
        chunkSizeWarningLimit: 2000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    const match = id.match(/node_modules\/([^/]+)/);
                    if (match) {
                        const packageName = match[1];
                        if (id.includes('node_modules/')) {
                            return `vendor_${packageName}`;
                        } else {
                            // Specify specific packages to group into smaller chunks
                            if (packageName === 'date-fns') {
                                return 'date_fns'; // Group date-fns into a separate chunk
                            }
                            if (packageName.startsWith('game-related-package')) {
                                return 'game'; // Group game-related packages into a separate chunk
                            }
                        }
                    }
                },
            },
        },
    },
});
