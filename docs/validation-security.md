# Validation & Sécurité (Laravel + AJAX)

## Objectif
Mettre en place une validation **serveur-first**, robuste, réutilisable et compatible formulaires classiques + AJAX.

## Composants implémentés

1. `app/Support/Security/InputSanitizer.php`
- Sanitization UTF-8.
- Suppression des caractères de contrôle.
- Neutralisation de patterns XSS courants (`<script>`, `on*=` et `javascript:`).
- Compatible tableaux imbriqués et fichiers uploadés.

2. `app/Http/Middleware/SanitizeInput.php`
- Applique une sanitization “soft” sur toutes les requêtes.

3. `app/Http/Middleware/EnsureIdempotency.php`
- Anti double soumission (header `Idempotency-Key` / `X-Request-Id` / champ `_request_id`).
- Retour `409` si requête déjà soumise récemment.

4. `app/Http/Requests/SecureFormRequest.php`
- Base FormRequest commune.
- Sanitization stricte avant validation.
- Réponse JSON standardisée pour AJAX/API en cas d’erreur `422`.

5. FormRequests concrets
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/ResendVerificationRequest.php`
- `app/Http/Requests/Profile/UpdateProfileRequest.php`
- `app/Http/Requests/Security/StoreValidationDemoRequest.php` (exemple complet)

6. Frontend sécurisé
- `public/vendor/client/js/secure-form.js`
  - Génère un `_request_id`.
  - Soumet en AJAX avec headers sécurisés.
  - Gère erreurs `422` champ par champ.
- Exemples:
  - `public/vendor/client/js/profile-settings.js`
  - `public/vendor/client/js/validation-demo.js`

7. Messages FR
- `lang/fr/validation.php`
- `lang/fr/security.php`

## Contrôleurs branchés
- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/SecurityValidationDemoController.php` (bonus demo AJAX)

## Route démo complète
- `GET /security/validation-demo`
- `POST /security/validation-demo`

Vue: `resources/views/security/validation-demo.blade.php`

## Bonnes pratiques de sortie (XSS)
- En Blade, utiliser `{{ $value }}` (échappement automatique).
- Éviter `{!! !!}` sauf contenu explicitement nettoyé.

## SQL Injection
- Utiliser Eloquent / Query Builder avec bindings.
- Éviter les requêtes SQL brutes concaténées.
