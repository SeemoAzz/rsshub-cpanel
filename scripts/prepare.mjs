import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const appDir = path.join(root, 'RSSHub');

function run(cmd, cwd = root) {
  console.log(`> ${cmd}`);
  execSync(cmd, { cwd, stdio: 'inherit', shell: true });
}

function pnpm(args, cwd) {
  run(`npx --yes pnpm ${args}`, cwd);
}

if (!fs.existsSync(appDir)) {
  console.log('Clonage de RSSHub...');
  run(`git clone --depth 1 https://github.com/DIYgod/RSSHub.git "${appDir}"`);
}

console.log('Installation des dépendances RSSHub...');
pnpm('install', appDir);

console.log('Build RSSHub...');
pnpm('build', appDir);

console.log('');
console.log('Build RSSHub terminé.');
console.log('Prochaine étape : cp .env.example .env puis configurez TWITTER_AUTH_TOKEN');
