import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: 'bhavin.plexuscard.link',  // ← your domain
        port: 5173,
        hmr: {
            host: 'bhavin.plexuscard.link',  // ← same domain
        },
    },
});