# Nexus CRM

Application CRM SaaS multi-tenant (Laravel 10) avec modules metier, marketplace interne, onboarding guide, moteur d'automation intelligent et extensions Google / communication.

## Stack technique

- PHP 8.1+ (8.2 recommande)
- Laravel 10
- MySQL 8+
- Node.js 18+ et npm
- Redis (cache + session par defaut)
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
- `CACHE_DRIVER`, `SESSION_DRIVER`, `REDIS_*` si vous gardez Redis
- variables `MAIL_*` (emails verification + bienvenue)
- variables Google / Slack OAuth (voir section OAuth ci-dessous)

### 4.b Redis (important)

Le projet utilise par defaut :

- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`

Deux options possibles :

1. Installer et lancer Redis localement / sur le serveur
2. Ou temporairement repasser en mode simple dans `.env` :

```dotenv
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Exemple de verification Redis :

```bash
redis-cli ping
```

Reponse attendue :

```text
PONG
```

### 5. Base de donnees

```bash
php artisan migrate --seed
```

Si vous utilisez la queue `database`, penser aussi a creer la table des jobs :

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
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

### 9. Verification rapide apres installation

```bash
php artisan optimize:clear
php artisan about
php artisan route:list
```

## 2) Configuration OAuth (Google / Slack)

### Google Cloud Console

Dans Google Cloud Console :

1. Creer un projet Google Cloud.
2. Activer les APIs necessaires selon les apps utilisees :
   - Google Drive API
   - Google Calendar API
   - Google Sheets API
   - Google Docs API
   - Gmail API
3. Creer un OAuth Client ID (type Web).
4. Ajouter les URI de redirection autorises (exact match).

URI de callback utilisees par l'application:

- `/auth/google/callback`
- `/extensions/google-drive/oauth/callback`
- `/extensions/google-calendar/oauth/callback`
- `/extensions/google-sheets/oauth/callback`
- `/extensions/google-docx/oauth/callback`
- `/extensions/google-gmail/oauth/callback`
- `/extensions/google-meet/oauth/callback`

Exemple local (a adapter a votre domaine):

- `http://127.0.0.1:8000/auth/google/callback`
- `http://127.0.0.1:8000/extensions/google-drive/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-calendar/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-sheets/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-docx/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-gmail/oauth/callback`
- `http://127.0.0.1:8000/extensions/google-meet/oauth/callback`

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

GOOGLE_MEET_CLIENT_ID=
GOOGLE_MEET_CLIENT_SECRET=
GOOGLE_MEET_REDIRECT_URI=
```

### Slack OAuth

Pour l'extension Slack :

1. Creer une application dans Slack API.
2. Configurer les scopes OAuth necessaires.
3. Ajouter l'URL de callback autorisee :

- `http://127.0.0.1:8000/extensions/slack/oauth/callback`

Variables `.env` attendues :

```dotenv
SLACK_CLIENT_ID=
SLACK_CLIENT_SECRET=
SLACK_REDIRECT_URI=/extensions/slack/oauth/callback
SLACK_API_BASE_URL=https://slack.com/api
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
- l'installation d'une app ne suffit pas toujours : certaines apps doivent ensuite etre connectees via OAuth depuis leur propre ecran

Apps avec connexion externe a faire apres installation :

- Google Drive
- Google Calendar
- Google Sheets
- Google Docs
- Google Gmail
- Google Meet
- Slack

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

## 5) Temps reel Socket.IO (Slack / Chatbot)

Deux extensions utilisent un serveur Node Socket.IO separe :

- `extensions/slack/socket-server`
- `extensions/chatbot/socket-server`

### Slack Socket.IO

```bash
cd extensions/slack/socket-server
npm install
npm start
```

Port par defaut :

- `6002`

Variables `.env` utiles :

```dotenv
SLACK_SOCKET_IO_ENABLED=true
SLACK_SOCKET_IO_URL=http://127.0.0.1:6002
SLACK_SOCKET_IO_PATH=/socket.io
SLACK_SOCKET_IO_NAMESPACE=/
SLACK_SOCKET_IO_EMIT_URL=http://127.0.0.1:6002/emit
SLACK_SOCKET_IO_SERVER_TOKEN=
```

### Chatbot Socket.IO

```bash
cd extensions/chatbot/socket-server
npm install
npm start
```

Port par defaut :

- `6003`

Variables `.env` utiles :

```dotenv
CHATBOT_SOCKET_IO_ENABLED=true
CHATBOT_SOCKET_IO_URL=http://127.0.0.1:6003
CHATBOT_SOCKET_IO_PATH=/socket.io
CHATBOT_SOCKET_IO_NAMESPACE=/
CHATBOT_SOCKET_IO_EMIT_URL=http://127.0.0.1:6003/emit
CHATBOT_SOCKET_IO_SERVER_TOKEN=
```

## 6) Queue et traitements en arriere-plan

Par defaut, `.env.example` utilise :

```dotenv
QUEUE_CONNECTION=sync
```

Ce mode fonctionne en local, mais pour une vraie production il est recommande d'utiliser `redis` ou `database`.

### Exemple avec Redis

```dotenv
QUEUE_CONNECTION=redis
```

Puis lancer un worker :

```bash
php artisan queue:work --queue=default,automation --tries=3
```

Le moteur d'automation peut fonctionner en `sync`, mais il est plus propre en queue asynchrone pour les actions longues :

- emails
- Google APIs
- automatisations futures

## 7) Deploiement (checklist rapide)

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

Si vous utilisez Redis / queue / temps reel en production, prevoir aussi :

- Redis demarre
- worker queue demarre
- serveur Octane demarre
- serveurs Socket.IO Slack / Chatbot demarres si ces extensions sont actives

## 8) Troubleshooting

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

### Sessions / cache Redis ne fonctionnent pas

Verifier :

- que Redis est demarre
- que `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` sont corrects
- ou repasser provisoirement `CACHE_DRIVER=file` et `SESSION_DRIVER=file`

### Socket.IO Slack / Chatbot ne diffuse pas

Verifier :

- que le serveur Node correspondant tourne sur le bon port
- que `*_SOCKET_IO_EMIT_URL` pointe vers le bon serveur
- que `*_SOCKET_IO_SERVER_TOKEN` correspond des deux cotes si vous activez le token
- que le firewall local ne bloque pas `6002` / `6003`

### Octane sous Windows: erreurs signaux/permissions

Si RoadRunner est present en `rr.exe`, verifier aussi la presence de `rr` a la racine si necessaire (certaines versions/scripts l'attendent).

## 9) Documentation interne utile

Le projet inclut une couche de securite/validation centralisee, ainsi qu'un moteur d'automation transverse. Voir :

- `docs/validation-security.md`
- `docs/automation-system.md`

---

Pour une installation propre sur un autre serveur, suivre au minimum :

1. sections `1 -> 4`
2. section `5` si vous utilisez Slack / Chatbot temps reel
3. section `6` si vous passez la queue en asynchrone
4. section `7` pour le deploiement final
