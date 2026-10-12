import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

function legacyCssMediaQueries() {
    return {
        name: 'legacy-css-media-queries',
        generateBundle(_options, bundle) {
            for (const chunk of Object.values(bundle)) {
                if (chunk.type !== 'asset' || !chunk.fileName.endsWith('.css')) {
                    continue;
                }

                const source = typeof chunk.source === 'string'
                    ? chunk.source
                    : chunk.source.toString();

                chunk.source = source
                    .replaceAll(/@media \(width\s*>=\s*(\d+)px\)/g, '@media (min-width: $1px)')
                    .replaceAll(/@media \(width\s*<=\s*(\d+)px\)/g, '@media (max-width: $1px)');
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        legacyCssMediaQueries(),
    ],
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
        },
    },
});
