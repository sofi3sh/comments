import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { createSvgIconsPlugin } from 'vite-plugin-svg-icons';
import path from 'node:path';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        // Tell Vite what URL the browser will use for HMR websocket
        hmr: { host: 'platform.localhost' },
        cors: true,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/backpack-custom.css',
                'resources/js/app.js',
                'resources/js/editorjs.js',
                'resources/js/articles/article-auto-translate.js',
                'resources/js/articles/article-autosave.js',
                'resources/js/articles/article-content-uniqueness.js',
                'resources/js/articles/article-character-counter.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
        createSvgIconsPlugin({
            iconDirs: [
                path.resolve(process.cwd(), 'resources/icons'),
            ],
            symbolId: 'icon-[name]',
        }),
    ],
});
