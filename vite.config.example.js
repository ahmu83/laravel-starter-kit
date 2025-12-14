import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { globSync } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            publicDirectory: '../app',
            input: [
                // 'resources/css/app.css',
                // 'resources/js/app.js',
                ...globSync('resources/assets/pages/**/*.{scss,css,js}'),
                ...globSync('resources/assets/components/**/*.{scss,css,js}'),
                ...globSync('resources/assets/layouts/**/*.{scss,css,js}'),

                'resources/assets/js/app.js',
                'resources/assets/sass/app.scss',

            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});


