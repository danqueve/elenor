import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Self-hosted, woff2 only (latin subset: covers Spanish). Regular for
            // text, SemiBold for buttons and prices, Bold for headings.
            fonts: [
                local('Montserrat', {
                    variants: [
                        { src: 'resources/fonts/montserrat-latin-400-normal.woff2', weight: 400 },
                        { src: 'resources/fonts/montserrat-latin-600-normal.woff2', weight: 600 },
                        { src: 'resources/fonts/montserrat-latin-700-normal.woff2', weight: 700 },
                    ],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
