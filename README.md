# RSSHub — déploiement cPanel Namecheap

Projet **indépendant** pour héberger [RSSHub](https://github.com/DIYgod/RSSHub) sur un hébergement Namecheap (cPanel + Node.js).

Utilisé par le bot **Bot Récupération News** via l’URL configurée dans `rsshub_url`.

---

## Prérequis

| Élément | Détail |
| --- | --- |
| Hébergement Namecheap | Plan **Stellar Plus**, **Stellar Business** ou **VPS** (Node.js requis) |
| cPanel | Accès avec **Setup Node.js App** activé |
| Node.js local | Version **22+** (pour préparer le build) |
| Git | Pour cloner RSSHub en local |
| Sous-domaine | Recommandé : `rsshub.votredomaine.com` |

> Les plans Stellar **Basic** / **EasyWP** ne proposent pas Node.js — vérifiez votre offre dans le portail Namecheap avant de commencer.

---

## Vue d’ensemble

```
1. Préparer le projet en local (clone + build RSSHub)
2. Configurer le token X (Twitter)
3. Créer le sous-domaine dans cPanel
4. Uploader les fichiers sur le serveur
5. Créer l’application Node.js dans cPanel
6. Configurer les variables d’environnement
7. Installer les dépendances et démarrer
8. Activer HTTPS (SSL)
9. Tester l’instance
10. Connecter le bot
```

---

## Étape 1 — Préparer le projet en local

### Windows

```powershell
cd "c:\Users\simohammed\Desktop\rsshub-cpanel"
.\scripts\prepare.ps1
```

### Linux / macOS

```bash
cd /chemin/vers/rsshub-cpanel
chmod +x scripts/prepare.sh
./scripts/prepare.sh
```

Ce script :

1. Installe `dotenv` à la racine du wrapper
2. Clone [RSSHub](https://github.com/DIYgod/RSSHub) dans `RSSHub/` (si absent)
3. Installe les dépendances RSSHub avec `pnpm`
4. Compile RSSHub (`RSSHub/dist/`)
5. Crée `.env` à partir de `.env.example` (s’il n’existe pas)

Vérifiez que le build a réussi :

```powershell
Test-Path RSSHub\dist\index.mjs   # doit retourner True
```

---

## Étape 2 — Configurer le token X (Twitter)

Les routes `/twitter/*` nécessitent un cookie `auth_token` valide.

1. Connectez-vous sur [x.com](https://x.com) dans votre navigateur
2. Ouvrez les outils développeur (**F12**) → **Application** → **Cookies** → `https://x.com`
3. Copiez la valeur du cookie **`auth_token`**
   - Ou utilisez l’extension [Cookie-Editor](https://cookie-editor.cgagnier.ca/)
4. Collez-la dans `.env` :

```powershell
copy .env.example .env
notepad .env
```

```env
TWITTER_AUTH_TOKEN=votre_auth_token_ici
```

> Le token expire après un certain temps. En cas d’erreur 403/503 sur les routes Twitter, régénérez-le et mettez à jour `.env` **et** les variables d’environnement cPanel.

---

## Étape 3 — Créer le sous-domaine dans cPanel

1. Connectez-vous à **cPanel** (Namecheap → **Hosting List** → **Go to cPanel**)
2. Section **Domains** → **Subdomains**
3. Créez un sous-domaine :
   - **Subdomain** : `rsshub`
   - **Domain** : `votredomaine.com`
   - **Document Root** : laissez la valeur par défaut ou notez le chemin (ex. `/home/VOTRE_USER/rsshub.votredomaine.com`)
4. Cliquez **Create**

> Vous utiliserez ce sous-domaine comme **Application URL** lors de la création de l’app Node.js.

---

## Étape 4 — Uploader les fichiers sur le serveur

### Option A — Upload ZIP (recommandé)

En local :

```powershell
.\scripts\pack-deploy.ps1
```

Cela crée `deploy.zip` contenant : `app.js`, `package.json`, `.env.example`, `RSSHub/dist/`, `RSSHub/node_modules/`, etc.

Sur cPanel :

1. **File Manager** → naviguez vers `/home/VOTRE_USER/`
2. Créez le dossier **`rsshub`** (ex. `/home/VOTRE_USER/rsshub`)
3. Entrez dans ce dossier
4. Cliquez **Upload** → uploadez `deploy.zip`
5. Retournez dans le dossier → clic droit sur `deploy.zip` → **Extract**
6. Supprimez `deploy.zip` après extraction (optionnel, libère de l’espace)

### Option B — Upload complet via File Manager / FTP

Uploadez **tout le dossier** `rsshub-cpanel` **sauf** :

- `node_modules/` à la racine (seul `dotenv` est installé sur le serveur via npm)
- `.git/` si présent

**Fichiers obligatoires sur le serveur :**

```
/home/VOTRE_USER/rsshub/
├── app.js
├── package.json
├── package-lock.json
├── .env                    ← à créer (voir étape 6)
├── .env.example
└── RSSHub/
    ├── dist/               ← build compilé
    ├── node_modules/       ← requis (build lourd à refaire sur le serveur)
    ├── assets/
    ├── public/
    └── package.json
```

### Créer le fichier `.env` sur le serveur

Dans **File Manager**, dans `/home/VOTRE_USER/rsshub/` :

1. Copiez `.env.example` → renommez la copie en **`.env`**
2. Éditez `.env` et ajoutez votre `TWITTER_AUTH_TOKEN`

---

## Étape 5 — Créer l’application Node.js dans cPanel

1. cPanel → section **Software** → **Setup Node.js App**
2. Cliquez **Create Application**
3. Remplissez les champs :

| Champ | Valeur |
| --- | --- |
| **Node.js version** | **22.x** (ou la plus récente disponible) |
| **Application mode** | **Production** |
| **Application root** | `/home/VOTRE_USER/rsshub` |
| **Application URL** | `rsshub.votredomaine.com` |
| **Application startup file** | `app.js` |

4. Cliquez **Create**

> Remplacez `VOTRE_USER` par votre identifiant cPanel (visible en haut de File Manager, ex. `abcxyz`).

---

## Étape 6 — Configurer les variables d’environnement

Dans **Setup Node.js App**, ouvrez votre application → section **Environment variables** → **Add variable** pour chaque entrée :

| Variable | Valeur | Description |
| --- | --- | --- |
| `TWITTER_AUTH_TOKEN` | `votre_token` | Cookie X pour les routes Twitter |
| `CACHE_TYPE` | `memory` | Cache en RAM (pas de Redis requis) |
| `LISTEN_INADDR_ANY` | `1` | Écoute sur toutes les interfaces (Apache/Passenger) |
| `ENABLE_CLUSTER` | `0` | Un seul processus (mutualisé) |
| `NODE_ENV` | `production` | Mode production |

Variables optionnelles :

| Variable | Valeur | Description |
| --- | --- | --- |
| `ACCESS_KEY` | `ma_cle_secrete` | Protège l’instance (ajoutez `?key=...` aux URLs) |

> **Ne définissez pas `PORT` manuellement** — cPanel/Passenger l’injecte automatiquement.

Cliquez **Save** après chaque variable.

---

## Étape 7 — Installer les dépendances et démarrer

Toujours dans **Setup Node.js App**, sur la fiche de votre application :

1. Cliquez **Run NPM Install**
   - Installe `dotenv` (dépendance racine du wrapper)
   - Attendez le message de succès
2. Cliquez **Restart** (ou **Stop** puis **Start**)

### Vérifier les logs

En cas de problème, consultez :

- **Setup Node.js App** → bouton **Open logs** ou lien vers les logs Passenger
- **File Manager** → `/home/VOTRE_USER/rsshub/stderr.log` (si présent)

Erreur fréquente :

```
RSSHub/dist/index.mjs introuvable
```

→ Relancez `.\scripts\prepare.ps1` en local, recréez `deploy.zip`, re-uploadez.

---

## Étape 8 — Activer HTTPS (SSL)

1. cPanel → **Security** → **SSL/TLS Status**
2. Cochez votre sous-domaine `rsshub.votredomaine.com`
3. Cliquez **Run AutoSSL** (Let’s Encrypt gratuit via Namecheap)

Ou : **SSL/TLS** → **Manage SSL sites** → installez un certificat pour le sous-domaine.

Attendez quelques minutes, puis testez en `https://`.

---

## Étape 9 — Tester l’instance

Ouvrez dans le navigateur :

```
https://rsshub.votredomaine.com/twitter/user/Reuters
```

Vous devez obtenir un flux RSS XML.

Autres tests utiles :

| URL | Attendu |
| --- | --- |
| `https://rsshub.votredomaine.com/` | Page d’accueil RSSHub |
| `https://rsshub.votredomaine.com/bbc/world` | Flux BBC (sans token X) |

Si vous avez défini `ACCESS_KEY` :

```
https://rsshub.votredomaine.com/twitter/user/Reuters?key=ma_cle_secrete
```

---

## Étape 10 — Connecter le bot

Dans le projet **Bot Récupération News**, mettez à jour `config.yaml` :

```yaml
rsshub_url: https://rsshub.votredomaine.com
```

Ou via l’interface web → **Configuration** → **URL RSSHub**.

Le bot utilise **x2rss** en fallback si RSSHub est indisponible.

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
| Page blanche / 503 | Consultez les logs Passenger et `stderr.log` dans le dossier de l’app |
| `RSSHub/dist/index.mjs` introuvable | Relancez `prepare.ps1` en local, re-uploadez `deploy.zip` |
| Routes Twitter 403/503 | Régénérez `TWITTER_AUTH_TOKEN`, mettez à jour `.env` **et** cPanel |
| Mémoire insuffisante au build | Buildez toujours en local ; uploadez `dist/` + `node_modules/` |
| `npm install` échoue sur le serveur | Uploadez `RSSHub/node_modules/` depuis votre PC via ZIP |
| App ne démarre pas après redémarrage cPanel | Setup Node.js App → **Restart** ; vérifiez que `app.js` est le startup file |
| Erreur « Application root not found » | Vérifiez le chemin exact dans File Manager (`/home/USER/rsshub`) |
| Sous-domaine affiche la page par défaut | L’Application URL doit correspondre au sous-domaine créé à l’étape 3 |
| SSL non actif | Relancez AutoSSL ; attendez la propagation DNS (jusqu’à 24 h) |
| `EADDRINUSE` ou port | Ne définissez pas `PORT` dans `.env` — laissez Passenger gérer |

### Commandes utiles cPanel (terminal SSH si disponible)

```bash
cd ~/rsshub
node -v                    # vérifier Node.js
ls RSSHub/dist/index.mjs   # vérifier le build
npm install                # réinstaller dotenv si besoin
```

---

## Mise à jour RSSHub

```powershell
cd RSSHub
git pull
cd ..
node scripts/prepare.mjs
.\scripts\pack-deploy.ps1
```

Puis sur cPanel :

1. Uploadez et extrayez le nouveau `deploy.zip` (écrasez les anciens fichiers)
2. Conservez votre `.env` existant sur le serveur
3. **Setup Node.js App** → **Run NPM Install** → **Restart**

---

## Notes

- **Cache mémoire** : pas de Redis requis (adapté à l’hébergement mutualisé Namecheap).
- **Cluster désactivé** : un seul processus pour limiter l’usage CPU/RAM.
- **Build local obligatoire** : les plans mutualisés n’ont pas assez de RAM pour compiler RSSHub sur le serveur.
- Le token X expire régulièrement — prévoyez une mise à jour périodique.
