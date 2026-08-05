# RSSHub — déploiement cPanel Namecheap

Projet **indépendant** pour héberger [RSSHub](https://github.com/DIYgod/RSSHub) sur un hébergement Namecheap (cPanel + Node.js).

Utilisé par le bot **Bot Récupération News** via l’URL configurée dans `rsshub_url`.

---

## Prérequis

| Élément | Détail |
| --- | --- |
| Hébergement Namecheap | Plan **Stellar Plus**, **Stellar Business** ou **VPS** (Node.js requis) |
| cPanel | Accès avec **Setup Node.js App** activé |
| Accès SSH | Recommandé (Terminal cPanel ou client SSH) |
| Node.js | Version **22+** (sur le serveur ou en local) |
| Git | Pour cloner le dépôt sur le serveur |
| Sous-domaine | Recommandé : `rsshub.votredomaine.com` |

> Les plans Stellar **Basic** / **EasyWP** ne proposent pas Node.js — vérifiez votre offre dans le portail Namecheap avant de commencer.

---

## Deux méthodes de déploiement

| Méthode | Quand l’utiliser |
| --- | --- |
| **[A — Git + SSH](#méthode-a--git--ssh-recommandée)** | Vous avez accès SSH/Terminal cPanel (recommandé) |
| **[B — ZIP depuis Windows](#méthode-b--upload-zip-depuis-windows)** | Pas de SSH, ou build échoue sur le serveur (mémoire insuffisante) |

> **Attention :** les scripts `.ps1` (`prepare.ps1`, `pack-deploy.ps1`) sont **Windows PowerShell uniquement**.  
> Sur le serveur Linux (SSH/bash), utilisez **`./scripts/prepare.sh`** — jamais `.\scripts\pack-deploy.ps1`.

---

## Méthode A — Git + SSH (recommandée)

### Vue d’ensemble

```
1. Créer le sous-domaine dans cPanel
2. Se connecter en SSH (Terminal cPanel)
3. git clone du dépôt
4. ./scripts/prepare.sh  (build RSSHub sur le serveur)
5. Configurer .env (token X)
6. Créer l’application Node.js dans cPanel
7. Variables d’environnement + npm install + Restart
8. SSL + test
```

### Étape A1 — Créer le sous-domaine dans cPanel

1. cPanel → **Domains** → **Subdomains**
2. **Subdomain** : `rsshub` · **Domain** : `votredomaine.com`
3. Cliquez **Create**

### Étape A2 — Ouvrir le terminal SSH

**Option 1 — Terminal cPanel :** cPanel → **Advanced** → **Terminal**

**Option 2 — Client SSH :**

```bash
ssh araszfcr@business112.web-hosting.com
```

(Remplacez par votre utilisateur et hostname Namecheap.)

### Étape A3 — Cloner le dépôt

```bash
cd ~
git clone https://github.com/SeemoAzz/rsshub-cpanel.git
cd rsshub-cpanel
```

Vérifiez Node.js (22+ requis) :

```bash
node -v
```

Si `node` est introuvable ou trop ancien, activez Node.js 22 dans cPanel → **Setup Node.js App**, puis rechargez le shell ou utilisez le chemin indiqué par cPanel (ex. `source /home/VOTRE_USER/nodevenv/rsshub-cpanel/22/bin/activate`).

### Étape A4 — Builder RSSHub sur le serveur

```bash
chmod +x scripts/prepare.sh
./scripts/prepare.sh
```

Ce script :

1. Installe `dotenv` à la racine
2. Clone [RSSHub](https://github.com/DIYgod/RSSHub) dans `RSSHub/`
3. Installe les dépendances avec `pnpm`
4. Compile RSSHub → `RSSHub/dist/`
5. Crée `.env` depuis `.env.example` si absent

Vérifiez le build :

```bash
ls RSSHub/dist/index.mjs
```

> Si le build échoue (mémoire insuffisante), utilisez la [méthode B (ZIP)](#méthode-b--upload-zip-depuis-windows) en buildant sur votre PC.

### Étape A5 — Configurer le token X

```bash
cp .env.example .env
nano .env
```

Ajoutez votre cookie `auth_token` :

```env
TWITTER_AUTH_TOKEN=votre_auth_token_ici
```

Sauvegardez : `Ctrl+O` → Entrée → `Ctrl+X`.

### Étape A6 — Créer l’application Node.js dans cPanel

1. cPanel → **Setup Node.js App** → **Create Application**
2. Paramètres :

| Champ | Valeur |
| --- | --- |
| **Node.js version** | 22.x |
| **Application mode** | Production |
| **Application root** | `/home/VOTRE_USER/rsshub-cpanel` |
| **Application URL** | `rsshub.votredomaine.com` |
| **Application startup file** | `app.js` |

3. Cliquez **Create**

> Exemple : si votre user cPanel est `araszfcr`, le root est `/home/araszfcr/rsshub-cpanel`.

### Étape A7 — Variables d’environnement

Dans **Setup Node.js App** → votre app → **Environment variables** :

| Variable | Valeur |
| --- | --- |
| `TWITTER_AUTH_TOKEN` | votre token X |
| `CACHE_TYPE` | `memory` |
| `LISTEN_INADDR_ANY` | `1` |
| `ENABLE_CLUSTER` | `0` |
| `NODE_ENV` | `production` |

> Ne définissez **pas** `PORT` — cPanel/Passenger le gère.

### Étape A8 — Démarrer l’application

1. **Run NPM Install**
2. **Restart**

Consultez les logs en cas d’erreur : **Open logs** ou `~/rsshub-cpanel/stderr.log`.

### Étape A9 — SSL et test

1. cPanel → **SSL/TLS Status** → **Run AutoSSL** pour `rsshub.votredomaine.com`
2. Testez : `https://rsshub.votredomaine.com/twitter/user/Reuters`

### Étape A10 — Connecter le bot

```yaml
rsshub_url: https://rsshub.votredomaine.com
```

---

## Méthode B — Upload ZIP depuis Windows

### Vue d’ensemble

```
1. Build en local (Windows)
2. Créer deploy.zip
3. Upload + extraction via File Manager cPanel
4. Créer l’app Node.js + variables + Restart
```

### Étape B1 — Préparer en local (Windows)

```powershell
cd "c:\Users\simohammed\Desktop\rsshub-cpanel"
.\scripts\prepare.ps1
copy .env.example .env
notepad .env
.\scripts\pack-deploy.ps1
```

Vérifiez :

```powershell
Test-Path RSSHub\dist\index.mjs   # True
```

### Étape B2 — Upload sur cPanel

1. **File Manager** → `/home/VOTRE_USER/`
2. Créez `rsshub` (ou uploadez directement dans `rsshub-cpanel` si déjà cloné)
3. Uploadez `deploy.zip` → **Extract**
4. Copiez `.env.example` → `.env` et ajoutez `TWITTER_AUTH_TOKEN`

Puis suivez les étapes **A6 à A9** (app Node.js, variables, SSL, test).

---

## Configurer le token X (Twitter)

Les routes `/twitter/*` nécessitent un cookie `auth_token` valide.

1. Connectez-vous sur [x.com](https://x.com)
2. **F12** → **Application** → **Cookies** → `https://x.com` → copiez **`auth_token`**
3. Collez-le dans `.env` :

**SSH (serveur) :**

```bash
cp .env.example .env
nano .env
```

**Windows (local) :**

```powershell
copy .env.example .env
notepad .env
```

```env
TWITTER_AUTH_TOKEN=votre_auth_token_ici
```

> Mettez à jour `.env` **et** les variables cPanel en cas d’erreur 403/503 sur Twitter.

---

## Structure du projet

```
rsshub-cpanel/
├── app.js              # Point d'entrée cPanel (Passenger)
├── package.json
├── .env                # Secrets (non versionné)
├── scripts/
│   ├── prepare.ps1     # Clone + build RSSHub (Windows)
│   ├── prepare.sh      # Clone + build RSSHub (Linux/macOS)
│   ├── prepare.mjs     # Logique de build partagée
│   └── pack-deploy.ps1 # Archive deploy.zip pour upload
└── RSSHub/             # Source RSSHub (git clone, non versionné)
    ├── dist/
    └── node_modules/
```

---

## Dépannage cPanel Namecheap

| Problème | Solution |
| --- | --- |
| `bash: .scriptspack-deploy.ps1: command not found` | Vous êtes sur **Linux/SSH** : utilisez `./scripts/prepare.sh`, pas `.\scripts\pack-deploy.ps1` (Windows) |
| `node: command not found` | Activez Node.js 22 dans **Setup Node.js App**, puis `source` l’environnement virtuel indiqué par cPanel |
| Page blanche / 503 | Logs Passenger ou `~/rsshub-cpanel/stderr.log` |
| `RSSHub/dist/index.mjs` introuvable | Relancez `./scripts/prepare.sh` (SSH) ou re-uploadez un ZIP buildé en local |
| Routes Twitter 403/503 | Régénérez `TWITTER_AUTH_TOKEN` dans `.env` **et** cPanel |
| Build échoue (mémoire) | Passez à la [méthode B](#méthode-b--upload-zip-depuis-windows) |
| Application root incorrect | Doit pointer vers le dossier cloné : `/home/VOTRE_USER/rsshub-cpanel` |
| SSL non actif | **SSL/TLS Status** → **Run AutoSSL** |

### Commandes utiles (SSH)

```bash
cd ~/rsshub-cpanel
node -v
ls RSSHub/dist/index.mjs
./scripts/prepare.sh
npm install
```

---

## Mise à jour RSSHub

### Via Git + SSH (recommandé)

```bash
cd ~/rsshub-cpanel
git pull
./scripts/prepare.sh
```

Puis cPanel → **Setup Node.js App** → **Restart**.

> Conservez votre `.env` — il n’est pas écrasé par `git pull`.

### Via ZIP (Windows)

```powershell
cd RSSHub; git pull; cd ..
node scripts/prepare.mjs
.\scripts\pack-deploy.ps1
```

Uploadez `deploy.zip` sur cPanel, puis **Restart**.

---

## Notes

- **Scripts** : `.ps1` = Windows · `.sh` = Linux/SSH (serveur cPanel)
- **Cache mémoire** : pas de Redis requis
- **Cluster désactivé** : un seul processus (mutualisé)
- Si le build SSH échoue faute de RAM, buildez en local et uploadez le ZIP
