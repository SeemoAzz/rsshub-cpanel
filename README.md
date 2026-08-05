# RSSHub PHP — cPanel Namecheap

Alternative **PHP** à RSSHub Node.js pour hébergement **Namecheap / cPanel**.  
Compatible avec le bot **Bot Récupération News** via les mêmes URLs RSSHub (`/twitter/user/...`).

> **Pourquoi PHP ?** Sur l'hébergement mutualisé Namecheap, Node.js est souvent instable (Passenger, limites mémoire, `pthread_create`). PHP et cURL fonctionnent nativement sur tous les plans cPanel.

---

## Prérequis

| Élément | Détail |
| --- | --- |
| Hébergement Namecheap | Tout plan **cPanel** (Stellar Basic inclus) |
| PHP | **7.4+** (8.x recommandé) |
| Extension PHP | `curl` activée |
| Compte X (Twitter) | Cookies `auth_token` + `ct0` |

---

## Déploiement sur cPanel

### Étape 1 — Créer le sous-domaine

1. cPanel → **Domains** → **Subdomains**
2. **Subdomain** : `rsshub` · **Domain** : `votredomaine.com`
3. **Create**

Notez le dossier document root (ex. `/home/user/rsshub.votredomaine.com`).

### Étape 2 — Uploader les fichiers

Uploadez **tout le contenu** de ce dépôt dans le dossier du sous-domaine :

```
index.php
.htaccess
config.php.example
lib/
cache/
```

Via **File Manager**, **FTP** ou Terminal cPanel :

```bash
cd ~/rsshub.votredomaine.com
git clone https://github.com/SeemoAzz/rsshub-cpanel.git .
```

### Étape 3 — Configurer

1. Renommez `config.php.example` → `config.php`
2. Éditez `config.php` :

```php
return [
    'TWITTER_AUTH_TOKEN' => 'votre_auth_token',
    'TWITTER_CT0' => 'votre_ct0',
    'CACHE_TTL' => 300,
];
```

**Obtenir les cookies X :**

1. Connectez-vous sur [x.com](https://x.com)
2. **F12** → **Application** → **Cookies** → `https://x.com`
3. Copiez `auth_token` et `ct0`

### Étape 4 — Permissions cache

```bash
chmod 755 cache
```

Ou via File Manager : dossier `cache/` → Permissions → `755`.

### Étape 5 — SSL et test

1. cPanel → **SSL/TLS Status** → **Run AutoSSL**
2. Testez :

```
https://rsshub.votredomaine.com/twitter/user/Reuters
```

### Étape 6 — Connecter le bot

```yaml
rsshub_url: https://rsshub.votredomaine.com
```

---

## Routes compatibles RSSHub

| URL | Description |
| --- | --- |
| `/twitter/user/Reuters` | Flux tweets (20 par défaut) |
| `/twitter/user/Reuters/30` | Limite à 30 tweets |
| `/twitter/user/Reuters/with_replies` | Inclure les réponses |
| `/twitter/user/Reuters/exclude_rts` | Exclure les retweets |
| `/twitter/user/Reuters/20/exclude_rts` | Combiner les options |

---

## Structure du projet

```
rsshub-cpanel/
├── index.php              # Routeur principal
├── .htaccess              # Réécriture URL (Apache)
├── config.php.example     # Configuration (copier → config.php)
├── lib/
│   ├── bootstrap.php
│   ├── Cache.php          # Cache fichier
│   ├── Http.php           # Requêtes cURL
│   ├── Rss.php            # Génération RSS XML
│   └── Twitter/
│       ├── Client.php     # API GraphQL X
│       └── UserFeed.php   # Route /twitter/user/:id
└── cache/                 # Cache (auto-créé, inscriptible)
```

---

## Dépannage

| Problème | Solution |
| --- | --- |
| Page blanche | Activez les logs PHP dans cPanel ; vérifiez PHP 7.4+ |
| `Configuration manquante` | Copiez `config.php.example` → `config.php` |
| `Token X invalide` | Régénérez `auth_token` et `ct0` dans `config.php` |
| `Impossible d'obtenir ct0` | Ajoutez `TWITTER_CT0` explicitement dans `config.php` |
| 404 sur `/twitter/user/...` | Vérifiez que `mod_rewrite` est actif et `.htaccess` présent |
| 503 / limite X | Attendez quelques minutes (rate limit) ; augmentez `CACHE_TTL` |
| Erreur cache | `chmod 755 cache` ou `chmod 777 cache` |

---

## Différences avec RSSHub Node.js

| | RSSHub Node.js | RSSHub PHP (ce projet) |
| --- | --- | --- |
| Hébergement cPanel | Stellar Plus+ (Node.js) | **Tous les plans** |
| Routes supportées | 1000+ | **Twitter `/twitter/user/*`** |
| Installation | Build lourd (pnpm, bundle) | **Upload + config.php** |
| Maintenance | Mises à jour fréquentes | Légère |

Ce projet couvre le cas d'usage principal du bot (flux Twitter). Pour d'autres sources (Reddit, YouTube, etc.), utilisez une instance RSSHub complète sur un VPS ou un service cloud.

---

## Mise à jour

```bash
cd ~/rsshub.votredomaine.com
git pull
```

Conservez votre `config.php` — il n'est pas versionné.
