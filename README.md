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
2. Terminal cPanel → git clone
3. Créer l’application Node.js dans cPanel  ← AVANT d’utiliser node
4. Activer l’environnement virtuel Node (commande source)
5. ./scripts/prepare.sh  (build RSSHub)
6. Configurer .env (token X)
7. Variables d’environnement + Run NPM Install + Restart
8. SSL + test
```

> **Important :** sur cPanel, `node` n’existe **pas** dans le Terminal tant que vous n’avez pas créé l’app Node.js et activé son environnement virtuel.

### Guide rapide — Terminal cPanel (copier-coller)

Vous avez déjà cloné le dépôt ? Suivez **dans cet ordre** :

**1. cPanel → Setup Node.js App → Create Application**

| Champ | Valeur pour `araszfcr` |
| --- | --- |
| Node.js version | 22.x (ou la plus récente) |
| Application mode | Production |
| Application root | `rsshub-cpanel` |
| Application URL | votre sous-domaine (ex. `rsshub.votredomaine.com`) |
| Application startup file | `app.js` |

Cliquez **Create**.

**2. Activer Node.js dans le Terminal**

cPanel → **Setup Node.js App** → icône **crayon (Edit)** → copiez la commande affichée :

> *Enter to the virtual environment. To enter to virtual environment, run the command:*

Elle ressemble à :

```bash
source /home/araszfcr/nodevenv/rsshub-cpanel/22/bin/activate && cd /home/araszfcr/rsshub-cpanel
```

Collez-la dans le **Terminal cPanel**, puis :

```bash
node -v          # doit afficher v22.x
chmod +x scripts/prepare.sh
./scripts/prepare.sh
```

**3. Token X + démarrage**

```bash
cp .env.example .env
nano .env        # ajoutez TWITTER_AUTH_TOKEN
```

Puis dans cPanel : **Environment variables** → **Run NPM Install** → **Restart**.

---

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

### Étape A3 — Cloner le dépôt (Terminal cPanel)

```bash
cd ~
git clone https://github.com/SeemoAzz/rsshub-cpanel.git
cd rsshub-cpanel
```

> Ne lancez **pas** `.\scripts\pack-deploy.ps1` — c’est un script **Windows**. Sur le Terminal cPanel, utilisez `./scripts/prepare.sh` (étape A5).

### Étape A4 — Créer l’application Node.js dans cPanel

**À faire avant** `./scripts/prepare.sh` — sinon `node: command not found`.

1. cPanel → **Setup Node.js App** → **Create Application**
2. Paramètres :

| Champ | Valeur |
| --- | --- |
| **Node.js version** | 22.x |
| **Application mode** | Production |
| **Application root** | `rsshub-cpanel` |
| **Application URL** | `rsshub.votredomaine.com` |
| **Application startup file** | `app.js` |

3. Cliquez **Create**

> Le **Application root** est relatif à `/home/VOTRE_USER/`. Pour `araszfcr` : `/home/araszfcr/rsshub-cpanel`.

### Étape A5 — Activer Node.js dans le Terminal

1. **Setup Node.js App** → icône **Edit** (crayon) sur votre app
2. Copiez la commande `source /home/.../nodevenv/.../bin/activate && cd ...`
3. Collez-la dans le Terminal cPanel

Vérifiez :

```bash
node -v    # v22.x
npm -v
```

Si `node` est toujours introuvable, vous n’avez pas exécuté la commande `source ...` ou l’app Node.js n’est pas encore créée.

### Étape A6 — Builder RSSHub sur le serveur

**Dans le même terminal** (environnement virtuel activé) :

```bash
cd ~/rsshub-cpanel
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

### Étape A7 — Configurer le token X

```bash
cp .env.example .env
nano .env
```

Ajoutez votre cookie `auth_token` :

```env
TWITTER_AUTH_TOKEN=votre_auth_token_ici
```

Sauvegardez : `Ctrl+O` → Entrée → `Ctrl+X`.

### Étape A8 — Variables d’environnement

Dans **Setup Node.js App** → votre app → **Environment variables** :

| Variable | Valeur |
| --- | --- |
| `TWITTER_AUTH_TOKEN` | votre token X |
| `CACHE_TYPE` | `memory` |
| `LISTEN_INADDR_ANY` | `1` |
| `ENABLE_CLUSTER` | `0` |
| `NODE_ENV` | `production` |

> Ne définissez **pas** `PORT` — cPanel/Passenger le gère.

### Étape A9 — Démarrer l’application

1. **Run NPM Install**
2. **Restart**

Consultez les logs en cas d’erreur : **Open logs** ou `~/rsshub-cpanel/stderr.log`.

### Étape A10 — SSL et test

1. cPanel → **SSL/TLS Status** → **Run AutoSSL** pour `rsshub.votredomaine.com`
2. Testez : `https://rsshub.votredomaine.com/twitter/user/Reuters`

### Étape A11 — Connecter le bot

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

Puis suivez les étapes **A4 à A10** (app Node.js, variables, SSL, test).

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
| `bash: .scriptspack-deploy.ps1: command not found` | Script **Windows** — sur Terminal cPanel utilisez `./scripts/prepare.sh` |
| `node: command not found` | **Normal** avant création de l’app Node.js. Créez l’app dans **Setup Node.js App**, puis exécutez la commande `source /home/.../nodevenv/.../bin/activate` copiée depuis l’écran Edit |
| `node -v` fonctionne mais `./scripts/prepare.sh` échoue | Relancez `./scripts/prepare.sh` **dans** l’environnement virtuel (`source ...` d’abord) |
| Page blanche / 503 | Logs Passenger ou `~/rsshub-cpanel/stderr.log` |
| `RSSHub/dist/index.mjs` introuvable | Relancez `./scripts/prepare.sh` (SSH) ou re-uploadez un ZIP buildé en local |
| Routes Twitter 403/503 | Régénérez `TWITTER_AUTH_TOKEN` dans `.env` **et** cPanel |
| Build échoue (mémoire) | Passez à la [méthode B](#méthode-b--upload-zip-depuis-windows) |
| Application root incorrect | Doit pointer vers le dossier cloné : `/home/VOTRE_USER/rsshub-cpanel` |
| SSL non actif | **SSL/TLS Status** → **Run AutoSSL** |

### Commandes utiles (Terminal cPanel)

```bash
# 1. Activer Node (commande copiée depuis Setup Node.js App → Edit)
source /home/araszfcr/nodevenv/rsshub-cpanel/22/bin/activate && cd /home/araszfcr/rsshub-cpanel

# 2. Vérifier et builder
node -v
ls RSSHub/dist/index.mjs
./scripts/prepare.sh
npm install
```

---

## Mise à jour RSSHub

### Via Git + Terminal cPanel

```bash
source /home/araszfcr/nodevenv/rsshub-cpanel/22/bin/activate && cd /home/araszfcr/rsshub-cpanel
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
