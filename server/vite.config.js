import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const directory = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@prototype': path.resolve(directory, '../src'),
            react: path.resolve(directory, 'node_modules/react'),
            'react-dom': path.resolve(directory, 'node_modules/react-dom'),
        },
    },
    server: {
        fs: {
            allow: [path.resolve(directory, '..')],
        },
    },
});
