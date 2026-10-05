import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { join, resolve, sep } from 'node:path';

const projectRoot = resolve(import.meta.dirname, '..');
const outputDirectory = resolve(projectRoot, 'vercel-static');
const publicDirectory = resolve(projectRoot, 'public');

if (!outputDirectory.startsWith(projectRoot + sep)) {
    throw new Error('The static output directory must be inside the project.');
}

const assetDirectory = join(publicDirectory, 'build');

if (!existsSync(join(assetDirectory, 'manifest.json'))) {
    throw new Error('Run the Vite build before preparing Vercel static assets.');
}

rmSync(outputDirectory, { recursive: true, force: true });
mkdirSync(outputDirectory, { recursive: true });
cpSync(assetDirectory, join(outputDirectory, 'build'), { recursive: true });

for (const filename of ['favicon.ico', 'robots.txt']) {
    const source = join(publicDirectory, filename);

    if (existsSync(source)) {
        cpSync(source, join(outputDirectory, filename));
    }
}
