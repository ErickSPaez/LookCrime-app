import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/breeze.css',
                'resources/js/app.js',
            ],
            publicDirectory: '../server/public',
            refresh: [
                '../server/app/**',
                '../server/resources/views/**',
                '../server/routes/**',
            ],
        }),
    ],
});
