import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { globSync } from 'glob';

export default defineConfig({
  plugins: [
    laravel({
      // publicDirectory: '../app',
      publicDirectory: './public',
      // optional, but explicit:
      // buildDirectory: 'build',
      input: [
        'resources/assets/js/app.js',
        'resources/assets/css/app.css',
        // 'resources/assets/sass/app.scss',

        ...globSync('resources/assets/pages/**/*.{scss,css,js}'),
        ...globSync('resources/assets/components/**/*.{scss,css,js}'),
        ...globSync('resources/assets/layouts/**/*.{scss,css,js}'),
      ],
      refresh: true,
    }),
  ],
  // base: '/app/build/',
  build: {
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
});
