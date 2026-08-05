import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const appDir = path.join(root, 'RSSHub');
const RSSHUB_SOURCE_ARCHIVE =
  'https://github.com/DIYgod/RSSHub/archive/refs/heads/master.tar.gz';
const RSSHUB_BUNDLE_URL =
  process.env.RSSHUB_BUNDLE_URL ||
  'https://github.com/SeemoAzz/rsshub-cpanel/releases/download/rsshub-bundle/rsshub-bundle.tar.gz';

function run(cmd, cwd = root) {
  console.log(`> ${cmd}`);
  execSync(cmd, { cwd, stdio: 'inherit', shell: true });
}

function runCapture(cmd, cwd = root) {
  return execSync(cmd, { cwd, encoding: 'utf8', shell: true }).trim();
}

function isRsshubReady(dir) {
  return fs.existsSync(path.join(dir, 'dist', 'index.mjs'));
}

function removeDir(dir) {
  if (fs.existsSync(dir)) {
    fs.rmSync(dir, { recursive: true, force: true });
  }
}

function hasCommand(name) {
  try {
    runCapture(`command -v ${name}`);
    return true;
  } catch {
    return false;
  }
}

function downloadFile(url, dest) {
  if (hasCommand('curl')) {
    run(`curl -fsSL -o "${dest}" "${url}"`);
    return;
  }
  if (hasCommand('wget')) {
    run(`wget -q -O "${dest}" "${url}"`);
    return;
  }
  throw new Error('curl ou wget requis pour télécharger RSSHub');
}

function extractTarball(archivePath, extractDir) {
  removeDir(extractDir);
  fs.mkdirSync(extractDir, { recursive: true });
  run(`tar -xzf "${archivePath}" -C "${extractDir}"`);
}

function installBundleContents(extractDir) {
  removeDir(appDir);
  fs.mkdirSync(appDir, { recursive: true });

  for (const item of ['dist', 'node_modules', 'package.json']) {
    const src = path.join(extractDir, item);
    if (!fs.existsSync(src)) {
      throw new Error(`Élément manquant dans le bundle: ${item}`);
    }
    fs.renameSync(src, path.join(appDir, item));
  }
}

function downloadPrebuiltBundle() {
  const archivePath = path.join(root, '.rsshub-bundle.tar.gz');
  const extractDir = path.join(root, '.rsshub-extract');

  try {
    console.log('Téléchargement du bundle RSSHub précompilé...');
    downloadFile(RSSHUB_BUNDLE_URL, archivePath);
    extractTarball(archivePath, extractDir);
    installBundleContents(extractDir);
  } finally {
    fs.rmSync(archivePath, { force: true });
    removeDir(extractDir);
  }
}

function downloadSourceViaTarball() {
  const archivePath = path.join(root, '.rsshub-master.tar.gz');
  const extractDir = path.join(root, '.rsshub-extract');

  try {
    console.log('Téléchargement des sources RSSHub via archive GitHub...');
    downloadFile(RSSHUB_SOURCE_ARCHIVE, archivePath);
    extractTarball(archivePath, extractDir);

    const extracted = fs
      .readdirSync(extractDir)
      .find((name) => name.startsWith('RSSHub-'));

    if (!extracted) {
      throw new Error('Archive RSSHub invalide');
    }

    removeDir(appDir);
    fs.renameSync(path.join(extractDir, extracted), appDir);
  } finally {
    fs.rmSync(archivePath, { force: true });
    removeDir(extractDir);
  }
}

function cloneViaGit() {
  console.log('Clonage de RSSHub via git...');
  run(
    `git -c pack.threads=1 clone --depth 1 --single-branch https://github.com/DIYgod/RSSHub.git "${appDir}"`,
  );
}

function fetchSource() {
  if (fs.existsSync(path.join(appDir, 'package.json'))) {
    return;
  }

  if (fs.existsSync(appDir)) {
    console.log('Dossier RSSHub incomplet détecté, nettoyage...');
    removeDir(appDir);
  }

  try {
    downloadSourceViaTarball();
  } catch (archiveError) {
    console.warn(`Archive GitHub échouée: ${archiveError.message}`);
    removeDir(appDir);

    try {
      cloneViaGit();
    } catch (gitError) {
      throw new Error(
        `Impossible de récupérer les sources RSSHub.\n` +
          `- Archive: ${archiveError.message}\n` +
          `- Git: ${gitError.message}`,
      );
    }
  }

  if (!fs.existsSync(path.join(appDir, 'package.json'))) {
    removeDir(appDir);
    throw new Error('Sources RSSHub invalides (package.json manquant)');
  }
}

function ensurePnpm() {
  try {
    runCapture('pnpm --version');
    return;
  } catch {
    // corepack ships with Node 22 and avoids npx downloading pnpm on each run
  }

  run('corepack enable');
  run('corepack prepare pnpm@10.34.5 --activate');
}

function pnpm(args, cwd) {
  ensurePnpm();
  const env =
    'NODE_OPTIONS="--max-old-space-size=512" UV_THREADPOOL_SIZE=2';
  run(
    `${env} pnpm ${args} --config.network-concurrency=1 --config.child-concurrency=1`,
    cwd,
  );
}

function buildFromSource() {
  fetchSource();

  console.log('Installation des dépendances RSSHub...');
  pnpm('install', appDir);

  console.log('Build RSSHub...');
  pnpm('build', appDir);

  console.log('Nettoyage des dépendances de développement...');
  pnpm('prune --prod', appDir);
}

function prepareRsshub() {
  if (isRsshubReady(appDir)) {
    console.log('RSSHub déjà prêt.');
    return;
  }

  if (fs.existsSync(appDir)) {
    console.log('Installation RSSHub incomplète détectée, nettoyage...');
    removeDir(appDir);
  }

  try {
    downloadPrebuiltBundle();
  } catch (bundleError) {
    console.warn(`Bundle précompilé indisponible: ${bundleError.message}`);
    console.warn('Tentative de compilation locale (peut échouer sur hébergement mutualisé)...');
    removeDir(appDir);

    try {
      buildFromSource();
    } catch (buildError) {
      throw new Error(
        `Impossible d'installer RSSHub sur ce serveur.\n` +
          `- Bundle précompilé: ${bundleError.message}\n` +
          `- Build local: ${buildError.message}\n` +
          `Déclenchez le workflow GitHub "Build RSSHub bundle", attendez la release, puis relancez ./scripts/prepare.sh`,
      );
    }
  }

  if (!isRsshubReady(appDir)) {
    removeDir(appDir);
    throw new Error('RSSHub installé mais dist/index.mjs est introuvable');
  }
}

prepareRsshub();

console.log('');
console.log('RSSHub prêt.');
console.log('Prochaine étape : cp .env.example .env puis configurez TWITTER_AUTH_TOKEN');
