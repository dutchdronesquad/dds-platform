import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

const ddevPrimaryUrl = process.env.DDEV_PRIMARY_URL;
const ddevHostname = ddevPrimaryUrl
    ? new URL(ddevPrimaryUrl).hostname
    : undefined;
const vitePort = Number(process.env.VITE_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: vitePort,
        strictPort: true,
        ...(ddevPrimaryUrl
            ? {
                  origin: `${ddevPrimaryUrl}:${vitePort}`,
                  cors: {
                      origin: [ddevPrimaryUrl],
                  },
                  hmr: {
                      host: ddevHostname,
                      protocol: 'wss',
                      clientPort: vitePort,
                  },
              }
            : {}),
    },
});
