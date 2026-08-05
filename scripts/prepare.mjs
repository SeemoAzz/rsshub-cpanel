import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const appDir = path.join(root, 'RSSHub');
const RSSHUB_ARCHIVE =
  'https://github.com/DIYgod/RSSHub/archive/refs/heads/master.tar.gz';

function run(cmd, cwd = root) {
  console.log(`> ${cmd}`);
  execSync(cmd, { cwd, stdio: 'inherit', shell: true });
}

function runCapture(cmd, cwd = root) {
  return execSync(cmd, { cwd, encoding: 'utf8', shell: true }).trim();
}

function pnpm(args, cwd) {
  run(`npx --yes pnpm ${args}`, cwd);
}

function isValidRsshubSource(dir) {
  return fs.existsSync(path.join(dir, 'package.json'));
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

function downloadArchive(dest) {
  if (hasCommand('curl')) {
    run(`curl -fsSL -o "${dest}" "${RSSHUB_ARCHIVE}"`);
    return;
  }
  if (hasCommand('wget')) {
    run(`wget -q -O "${dest}" "${RSSHUB_ARCHIVE}"`);
    return;
  }
  throw new Error('curl ou wget requis pour télécharger RSSHub');
}

function extractArchive(archivePath, extractDir) {
  removeDir(extractDir);
  fs.mkdirSync(extractDir, { recursive: true });
  run(`tar -xzf "${archivePath}" -C "${extractDir}"`);
}

function downloadViaTarball() {
  const archivePath = path.join(root, '.rsshub-master.tar.gz');
  const extractDir = path.join(root, '.rsshub-extract');

  try {
    console.log('Téléchargement de RSSHub via archive GitHub...');
    downloadArchive(archivePath);
    extractArchive(archivePath, extractDir);

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

function fetchRsshub() {
  if (isValidRsshubSource(appDir)) {
    return;
  }

  if (fs.existsSync(appDir)) {
    console.log('Dossier RSSHub incomplet détecté, nettoyage...');
    removeDir(appDir);
  }

  // Archive GitHub first: lighter on shared hosting thread limits than git clone.
  try {
    downloadViaTarball();
  } catch (archiveError) {
    console.warn(`Archive GitHub échouée: ${archiveError.message}`);
    removeDir(appDir);

    try {
      cloneViaGit();
    } catch (gitError) {
      throw new Error(
        `Impossible de récupérer RSSHub.\n` +
          `- Archive: ${archiveError.message}\n` +
          `- Git: ${gitError.message}\n` +
          `Relancez ./scripts/prepare.sh ou contactez l'hébergeur si les limites mémoire persistent.`,
      );
    }
  }

  if (!isValidRsshubSource(appDir)) {
    removeDir(appDir);
    throw new Error('RSSHub téléchargé mais source invalide (package.json manquant)');
  }
}

fetchRsshub();

console.log('Installation des dépendances RSSHub...');
pnpm('install', appDir);

console.log('Build RSSHub...');
pnpm('build', appDir);

console.log('');
console.log('Build RSSHub terminé.');
console.log('Prochaine étape : cp .env.example .env puis configurez TWITTER_AUTH_TOKEN');
