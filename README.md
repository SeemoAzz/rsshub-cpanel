# RSSHub — déploiement cPanel Namecheap (Terminal)

Projet pour héberger [RSSHub](https://github.com/DIYgod/RSSHub) sur un hébergement **Namecheap** via **cPanel** et le **Terminal cPanel**.

Utilisé par le bot **Bot Récupération News** via l’URL configurée dans `rsshub_url`.

---

## Prérequis

| Élément | Détail |
| --- | --- |
| Hébergement Namecheap | Plan **Stellar Plus** ou **Stellar Business** (Node.js requis) |
| cPanel | Accès avec **Setup Node.js App** et **Terminal** activés |
| Node.js | Version **22+** (fourni par cPanel) |
| Git | Pour cloner le dépôt sur le serveur |
| Sous-domaine | Recommandé : `rsshub.votredomaine.com` |

> Les plans Stellar **Basic** / **EasyWP** ne proposent pas Node.js — vérifiez votre offre dans le portail Namecheap avant de commencer.

---

## Déploiement via Terminal cPanel

### Vue d’ensemble

```
1. Créer le sous-domaine dans cPanel
2. Terminal cPanel → git clone
3. Créer l’application Node.js dans cPanel  ← AVANT d’utiliser node
4. Activer l’environnement virtuel Node (commande source)
5. ./scripts/prepare.sh  (clone + build RSSHub)
6. Configurer .env (token X)
7. Variables d’environnement + Run NPM Install + Restart
8. SSL + test
```

> **Important :** sur cPanel, `node` n’existe **pas** dans le Terminal tant que vous n’avez pas créé l’app Node.js et activé son environnement virtuel.

---

## Guide pas à pas

### Étape 1 — Créer le sous-domaine

1. cPanel → **Domains** → **Subdomains**
2. **Subdomain** : `rsshub` · **Domain** : `votredomaine.com`
3. Cliquez **Create**

### Étape 2 — Ouvrir le Terminal cPanel

cPanel → **Advanced** → **Terminal**

### Étape 3 — Cloner le dépôt

```bash
cd ~
git clone https://github.com/SeemoAzz/rsshub-cpanel.git
cd rsshub-cpanel
```

### Étape 4 — Créer l’application Node.js

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

> Le **Application root** est relatif à `/home/VOTRE_USER/`. Exemple : `/home/araszfcr/rsshub-cpanel`.

### Étape 5 — Activer Node.js dans le Terminal

1. **Setup Node.js App** → icône **Edit** (crayon) sur votre app
2. Copiez la commande affichée :

> *Enter to the virtual environment. To enter to virtual environment, run the command:*

Elle ressemble à :

```bash
source /home/VOTRE_USER/nodevenv/rsshub-cpanel/22/bin/activate && cd /home/VOTRE_USER/rsshub-cpanel
```

3. Collez-la dans le **Terminal cPanel**

Vérifiez :

```bash
node -v    # v22.x
npm -v
```

### Étape 6 — Builder RSSHub

**Dans le même terminal** (environnement virtuel activé) :

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

### Étape 7 — Configurer le token X

```bash
cp .env.example .env
nano .env
```

Ajoutez votre cookie `auth_token` :

```env
TWITTER_AUTH_TOKEN=votre_auth_token_ici
```

Sauvegardez : `Ctrl+O` → Entrée → `Ctrl+X`.

### Étape 8 — Variables d’environnement cPanel

Dans **Setup Node.js App** → votre app → **Environment variables** :

| Variable | Valeur |
| --- | --- |
| `TWITTER_AUTH_TOKEN` | votre token X |
| `CACHE_TYPE` | `memory` |
| `LISTEN_INADDR_ANY` | `1` |
| `ENABLE_CLUSTER` | `0` |
| `NODE_ENV` | `production` |

> Ne définissez **pas** `PORT` — cPanel/Passenger le gère.

### Étape 9 — Démarrer l’application

1. **Run NPM Install**
2. **Restart**

Consultez les logs en cas d’erreur : **Open logs** ou `~/rsshub-cpanel/stderr.log`.

### Étape 10 — SSL et test

1. cPanel → **SSL/TLS Status** → **Run AutoSSL** pour `rsshub.votredomaine.com`
2. Testez : `https://rsshub.votredomaine.com/twitter/user/Reuters`

### Étape 11 — Connecter le bot

```yaml
rsshub_url: https://rsshub.votredomaine.com
```

---

## Configurer le token X (Twitter)

Les routes `/twitter/*` nécessitent un cookie `auth_token` valide.

1. Connectez-vous sur [x.com](https://x.com)
2. **F12** → **Application** → **Cookies** → `https://x.com` → copiez **`auth_token`**
3. Collez-le dans `.env` via le Terminal :

```bash
nano .env
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
│   ├── prepare.sh      # Clone + build RSSHub (Terminal cPanel)
│   └── prepare.mjs     # Logique de build
└── RSSHub/             # Source RSSHub (git clone, non versionné)
    ├── dist/
    └── node_modules/
```

---

## Dépannage

| Problème | Solution |
| --- | --- |
| `node: command not found` | Créez l’app dans **Setup Node.js App**, puis exécutez la commande `source /home/.../nodevenv/.../bin/activate` copiée depuis l’écran Edit |
| `Cannot find module '.../nodevenv/.../lib/scripts/prepare.mjs'` | Mettez à jour le dépôt (`git pull`) — le script npm s’appelle `build-rsshub`, plus `prepare` |
| `unable to create thread: Resource temporarily unavailable` | Supprimez le dossier incomplet `rm -rf RSSHub`, mettez à jour le dépôt (`git pull`), relancez `./scripts/prepare.sh` (le script utilise maintenant une archive GitHub, plus légère que `git clone`) |
| `pthread_create: Resource temporarily unavailable` | Idem — supprimez `RSSHub/`, relancez le build ; si le build échoue encore, passez à **Stellar Business** |
| Page blanche / 503 | Logs Passenger ou `~/rsshub-cpanel/stderr.log` |
| `RSSHub/dist/index.mjs` introuvable | Relancez `./scripts/prepare.sh` dans l’environnement virtuel |
| Routes Twitter 403/503 | Régénérez `TWITTER_AUTH_TOKEN` dans `.env` **et** cPanel |
| Application root incorrect | Doit pointer vers le dossier cloné : `/home/VOTRE_USER/rsshub-cpanel` |
| SSL non actif | **SSL/TLS Status** → **Run AutoSSL** |

### Commandes utiles (Terminal cPanel)

```bash
# 1. Activer Node (commande copiée depuis Setup Node.js App → Edit)
source /home/VOTRE_USER/nodevenv/rsshub-cpanel/22/bin/activate && cd /home/VOTRE_USER/rsshub-cpanel

# 2. Vérifier et builder
node -v
ls RSSHub/dist/index.mjs
./scripts/prepare.sh
npm install
```

---

## Mise à jour RSSHub

```bash
source /home/VOTRE_USER/nodevenv/rsshub-cpanel/22/bin/activate && cd /home/VOTRE_USER/rsshub-cpanel
git pull
./scripts/prepare.sh
```

Puis cPanel → **Setup Node.js App** → **Restart**.

> Conservez votre `.env` — il n’est pas écrasé par `git pull`.

---

## Notes

- **Cache mémoire** : pas de Redis requis
- **Cluster désactivé** : un seul processus (hébergement mutualisé)
- Toutes les opérations se font via le **Terminal cPanel** — pas de build local ni d’upload ZIP
