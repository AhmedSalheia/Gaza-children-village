/**
 * Vite configuration — GCV DATA (F22)
 *
 * Changes from previous version:
 *  - Removed font CDN plugin (F22 requirement: self-hosted WOFF2 only).
 *  - Added Tailwind CSS v4 plugin.
 *  - Fonts are served from public/fonts/ via @font-face in _fonts.css.
 */
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // No font CDN — self-hosted WOFF2 via public/fonts/
        }),
        tailwindcss(),
    ],
    server: {
        allowedHosts: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
