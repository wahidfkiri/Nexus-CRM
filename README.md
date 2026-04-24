# Nexus CRM

Application CRM SaaS multi-tenant (Laravel 10) avec modules metier et extensions Google (Drive, Calendar, Sheets, Docs, Gmail), marketplace interne et onboarding guide.

## Stack technique

- PHP 8.2+
- Laravel 10
- MySQL 8+
- Node.js 18+ et npm
- Vite (front)
- Laravel Octane + RoadRunner (serveur haute performance)

## 1) Installation sur un nouvel environnement

### 1. Cloner le projet

```bash
git clone <URL_DU_REPO> nexus-crm
cd nexus-crm
```

### 2. Installer les dependances PHP

```bash
composer install
```

### 3. Installer les dependances front

```bash
npm install
```

### 4. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Renseigner ensuite au minimum dans `.env`:

- `APP_NAME`, `APP_ENV`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- variables `MAIL_*` (emails verification + bienvenue)
- variables Google OAuth (voir section OAuth ci-dessous)

### 5. Base de donnees

```bash
php artisan migrate --seed
```

### 6. Lien de stockage public

```bash
php artisan storage:link
```

### 7. Build front

- Mode developpement:

```bash
npm run dev
```

- Build production:

```bash
npm run build
```

### 8. Demarrage classique Laravel (sans Octane)

```bash
php artisan serve
```

## 2) Configuration Google OAuth (obligatoire pour les apps Google)

Dans Google Cloud Console:

1. Creer un projet Google Cloud.
2. Activer les APIs necessaires (Drive, Calendar, Sheets, Docs, Gmail).
3. Creer un OAuth Client ID (type Web).
4. Ajouter les URI de redirection autorises (exact match).

URI de callback utilisees par l'application:

- `/auth/google/callback`
- `/extensions/google-drive/oauth/callback`
- `/extensions/google-calendar/oauth/callback`
- `/extensions/google-sheets/oauth/callback`
- `/extensions/google-docx/oauth/callback`
- `/extensions/google-gmail/oauth/callback`

Exemple local (a adapter a votre domaine):

- `http://127.0.0.1:8000/auth/google/callback`
- `http://127.0.0.1:8000/extensions/google-drive/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-calendar/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-sheets/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-docx/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-gmail/oauth/callback`

Variables `.env` attendues:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_AUTH_REDIRECT_URI=

GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REDIRECT_URI=

GOOGLE_CALENDAR_CLIENT_ID=
GOOGLE_CALENDAR_CLIENT_SECRET=
GOOGLE_CALENDAR_REDIRECT_URI=

GOOGLE_SHEETS_CLIENT_ID=
GOOGLE_SHEETS_CLIENT_SECRET=
GOOGLE_SHEETS_REDIRECT_URI=

GOOGLE_DOCX_CLIENT_ID=
GOOGLE_DOCX_CLIENT_SECRET=
GOOGLE_DOCX_REDIRECT_URI=

GOOGLE_GMAIL_CLIENT_ID=
GOOGLE_GMAIL_CLIENT_SECRET=
GOOGLE_GMAIL_REDIRECT_URI=
```

## 3) Marketplace / Applications

Le catalogue des applications est seed automatiquement si la table `extensions` existe, mais vous pouvez forcer le seed:

```bash
php artisan extensions:seed
```

Reinitialiser puis reseeder le catalogue:

```bash
php artisan extensions:seed --reset
```

Activation par tenant:

- chaque tenant installe ses apps depuis `/applications` (Marketplace)
- si une app n'est pas active pour le tenant, elle ne doit pas apparaitre dans le menu global

## 4) Lancer Octane avec RoadRunner

### Prerequis Octane

- package `laravel/octane` installe
- package RoadRunner installe (`spiral/roadrunner-http`, `spiral/roadrunner-cli`)
- extension PHP `sockets` activee (important, surtout sous Windows/XAMPP)

Verifier:

```bash
php -m | findstr sockets
```

Si absent sous XAMPP, editer `C:\xampp\php\php.ini`:

```ini
extension=sockets
```

Puis relancer le terminal.

### Installation Octane (une seule fois)

```bash
php artisan octane:install --server=roadrunner
```

Cela genere notamment:

- `config/octane.php`
- `.rr.yaml`
- variable `.env`: `OCTANE_SERVER=roadrunner`

### Demarrer Octane

```bash
php artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8000 --rpc-port=6001 --workers=1 --task-workers=1 --max-requests=500
```

### Demarrer Octane en mode watch (dev)

```bash
php artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8000 --watch
```

### Statut / stop / restart

```bash
php artisan octane:status
php artisan octane:stop
php artisan octane:reload
```

## 5) Deploiement (checklist rapide)

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

## 6) Troubleshooting

### "Class ... not found" apres ajout package/extension

```bash
composer dump-autoload
php artisan optimize:clear
```

### "Base table not found"

```bash
php artisan migrate
```

### OAuth "Acces bloque : demande invalide"

Verifier:

- client id/secret corrects
- redirect URI exacte dans Google Cloud Console
- meme domaine/protocole (`http`/`https`) entre `.env` et Google Console

### Octane sous Windows: erreurs signaux/permissions

Si RoadRunner est present en `rr.exe`, verifier aussi la presence de `rr` a la racine si necessaire (certaines versions/scripts l'attendent).

## 7) Qualite et securite

Le projet inclut une couche de securite/validation centralisee (sanitize, idempotency, FormRequest serveur-first, gestion AJAX JSON). Voir:

- `docs/validation-security.md`

---

Pour une installation propre sur un autre serveur, suivez les sections 1 -> 5 dans l'ordre.
