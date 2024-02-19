import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import concat from 'rollup-plugin-concat';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/tailwind.css',
            'resources/js/app.ts',
        ]),
        react(),
        concat({
            // Specify the files to concatenate
            files: [
                "resources/vendor/theme/assets/js/script.js",
                "resources/vendor/theme/assets/js/extras.js",
                // You can add more files or even a directory
            ],
            // Specify the output file path relative to the build directory
            output: "resources/js/theme-script.js"
        }),
        // Repeat the same process for other sets of files
        concat({
            files: [
                "node_modules/@popperjs/core/dist/umd/popper.min.js",
                "node_modules/tippy.js/dist/tippy.umd.min.js",
            ],
            output: "resources/js/theme-vendor.js"
        }),
    ],
    optimizeDeps: {
        esbuildOptions: {
            tsconfig: 'tsconfig.json'
        }
    },
});
