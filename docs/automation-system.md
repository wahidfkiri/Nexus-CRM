# Système d'automation intelligent du CRM

## Objectif

Le CRM utilise un moteur d'automation **human-in-the-loop** :

- le système détecte une action métier importante
- il génère des suggestions intelligentes
- l'utilisateur accepte ou ignore
- si la suggestion est acceptée, une automation réelle est exécutée

Rien d'important n'est exécuté automatiquement sans validation utilisateur.

---

## Principe général

Le moteur repose sur 5 couches :

1. `Action métier`
2. `Domain Event`
3. `Suggestion Engine`
4. `Confirmation utilisateur`
5. `Execution Layer`

Flux simplifié :

```text
Action utilisateur
    ↓
Event métier Laravel
    ↓
CaptureAutomationSuggestions
    ↓
AutomationEngine
    ↓
SuggestionProviders
    ↓
automation_suggestions
    ↓
Validation utilisateur (modal / AJAX)
    ↓
automation_events
    ↓
Queue / Executor
    ↓
Apps et modules concernés
```

---

## Apps et modules concernés

### 1. Modules source déjà branchés

Ces modules déclenchent aujourd'hui des événements métier qui alimentent le moteur :

- `Clients`
  - événement : `client_created`
- `Facturation`
  - événements : `invoice_created`, `quote_created`
- `Projets`
  - événements : `project_created`, `project_task_created`
- `Utilisateurs`
  - événement : `user_invited`
- `Marketplace / Applications`
  - événement : `extension_activated`

### 2. Apps d'exécution déjà utilisées par le moteur

Ces apps peuvent être réellement appelées quand une suggestion est acceptée :

- `Google Gmail`
  - envoi d'email de bienvenue
  - envoi de facture
  - envoi de devis
  - relance email d'invitation
- `Google Calendar`
  - rendez-vous client
  - rappel facture
  - relance devis
  - kickoff projet
  - planification d'une tâche projet
  - rendez-vous d'onboarding
- `Google Drive`
  - création du dossier Drive d'un projet
- `Projets`
  - création de tâches de suivi
  - création de tâches d'onboarding
- `Chatbot / Slack`
  - création de canal projet

### 3. Apps prises en compte au niveau suggestions

Ces apps sont déjà reconnues par le système pour l'orientation ou l'ouverture de workspace :

- `Google Gmail`
- `Google Calendar`
- `Google Drive`
- `Google Meet`
- `Google Sheets`
- `Google Docs`
- `Projets`
- `Facturation`
- `Chatbot`
- `Slack`
- `Marketplace`

### 4. Ce que cela veut dire concrètement

- une app peut être **source d'un événement**
- une app peut être **cible d'une automation**
- une app peut être **suggérée à l'installation**
- une app peut être **ouverte comme prochain écran logique**

Le système est donc transversal : il ne dépend pas d'une seule extension.

---

## Tables utilisées

Le moteur repose sur 3 tables principales :

### `automation_suggestions`

Stocke les suggestions proposées à l'utilisateur.

Champs importants :

- `tenant_id`
- `user_id`
- `source_event`
- `source_type`
- `source_id`
- `type`
- `label`
- `payload`
- `meta`
- `status`
- `dedupe_key`
- `expires_at`

### `automation_events`

Stocke l'exécution d'une suggestion acceptée.

Champs importants :

- `tenant_id`
- `user_id`
- `event_name`
- `action_type`
- `payload`
- `status`
- `idempotency_key`
- `triggered_by_suggestion_id`

### `automation_logs`

Stocke le détail technique d'exécution.

Champs importants :

- `tenant_id`
- `automation_event_id`
- `automation_suggestion_id`
- `event_name`
- `action_type`
- `level`
- `status`
- `message`
- `response`
- `context`

---

## Comment le système fonctionne

## Étape 1 - Une action métier réussit

Exemples :

- création d'un client
- création d'un devis
- création d'une facture
- création d'un projet
- création d'une tâche
- invitation d'un utilisateur
- activation d'une app

À ce moment-là, le module concerné déclenche un event Laravel qui implémente :

```php
Vendor\Automation\Contracts\AutomationContextEvent
```

Cet event expose :

- le `tenant_id`
- le `user_id`
- le `source_event`
- le `source_type`
- le `source_id`
- le contexte métier utile

## Étape 2 - Le moteur capture l'événement

Le listener central :

```php
Vendor\Automation\Listeners\CaptureAutomationSuggestions
```

reçoit l'événement et transmet le contexte au :

```php
Vendor\Automation\Services\AutomationEngine
```

## Étape 3 - Les providers génèrent les suggestions

Le moteur interroge les providers enregistrés dans :

```php
packages/vendor/automation/config/automation.php
```

Chaque provider retourne une ou plusieurs suggestions.

Exemple :

```json
[
  {
    "type": "send_invoice_email",
    "label": "Envoyer la facture par email",
    "confidence": 0.90
  },
  {
    "type": "schedule_invoice_reminder",
    "label": "Planifier un rappel de paiement",
    "confidence": 0.84
  }
]
```

## Étape 4 - L'utilisateur valide

Les suggestions sont affichées dans la modal globale AJAX.

L'utilisateur peut :

- accepter une suggestion
- ignorer une suggestion
- accepter tout
- ignorer tout

Si l'utilisateur n'accepte pas, aucune exécution réelle n'a lieu.

## Étape 5 - Une automation est créée

Quand l'utilisateur accepte :

- la suggestion passe en `accepted`
- une ligne est créée dans `automation_events`
- un événement `AutomationEventQueued` est dispatché
- le job `ExecuteAutomationEventJob` prend le relais

## Étape 6 - L'action réelle est exécutée

Le moteur résout l'action via le `ActionRegistry`, puis exécute une classe dédiée :

- `SendEmailAutomationAction`
- `ScheduleCalendarAutomationAction`
- `CreateQuoteAutomationAction`
- `CreateProjectTaskAutomationAction`
- `CreateProjectDriveFolderAction`
- `CreateProjectChannelAction`
- `OpenExtensionWorkspaceAction`

Chaque action :

- vérifie le tenant
- vérifie si l'app cible est installée
- vérifie si l'authentification externe existe si nécessaire
- exécute l'action réelle
- écrit le résultat dans `automation_events` et `automation_logs`

---

## Règles importantes

### 1. Isolation multi-tenant stricte

Chaque suggestion et chaque exécution appartiennent à un `tenant_id`.

Le moteur refuse toute tentative de traitement sur un autre tenant.

### 2. Jamais d'exécution directe dans le controller

Les controllers métier ne doivent pas appeler Gmail, Calendar ou Drive directement pour la logique automation.

Ils doivent :

- faire leur travail métier
- dispatcher un event métier

### 3. Validation utilisateur obligatoire

Le système propose, l'humain décide.

### 4. Vérification des dépendances

Avant toute action, le moteur vérifie :

- si l'extension est active
- si le tenant est connecté à Google si nécessaire
- si les données source existent encore

### 5. Déduplication et idempotence

Le moteur évite :

- les suggestions en double
- les ré-exécutions multiples de la même suggestion

---

## Ce qui est déjà implémenté par type d'événement

## `client_created`

Suggestions actuelles :

- envoyer email de bienvenue
- créer un rendez-vous
- créer un devis

## `invoice_created`

Suggestions actuelles :

- envoyer la facture
- planifier un rappel
- créer une tâche de suivi

## `quote_created`

Suggestions actuelles :

- envoyer le devis
- planifier une relance
- créer une tâche de suivi

## `project_created`

Suggestions actuelles :

- planifier le kickoff
- créer le dossier Drive du projet
- créer le canal projet

## `project_task_created`

Suggestions actuelles :

- planifier la tâche dans Google Calendar
- créer le dossier Drive du projet si absent
- créer le canal projet si absent

## `user_invited`

Suggestions actuelles :

- envoyer un email d'accueil
- planifier un rendez-vous d'onboarding
- créer une tâche d'intégration

## `extension_activated`

Suggestions actuelles :

- ouvrir l'app activée pour terminer la configuration
- suggérer une app complémentaire quand cela a du sens

Exemples déjà prévus :

- `projects` → suggère `google-calendar`, `google-drive`, `chatbot/slack` si manquants
- `invoice` → suggère `google-gmail` si manquant
- `google-calendar` → suggère d'ouvrir `projects` si installé
- `google-drive` → suggère d'ouvrir `projects` si installé
- `google-gmail` → suggère d'ouvrir `invoice` si installé

---

## Fichiers principaux du système

### Noyau automation

- `packages/vendor/automation/config/automation.php`
- `packages/vendor/automation/src/AutomationServiceProvider.php`
- `packages/vendor/automation/src/Services/AutomationEngine.php`
- `packages/vendor/automation/src/Services/AutomationExecutor.php`
- `packages/vendor/automation/src/Listeners/CaptureAutomationSuggestions.php`
- `packages/vendor/automation/src/Listeners/QueueAutomationExecution.php`
- `packages/vendor/automation/src/Jobs/ExecuteAutomationEventJob.php`

### Providers

- `ClientCreatedSuggestionProvider`
- `InvoiceCreatedSuggestionProvider`
- `QuoteCreatedSuggestionProvider`
- `ProjectCreatedSuggestionProvider`
- `ProjectTaskCreatedSuggestionProvider`
- `UserInvitedSuggestionProvider`
- `ExtensionActivatedSuggestionProvider`

### Actions

- `SendEmailAutomationAction`
- `ScheduleCalendarAutomationAction`
- `CreateQuoteAutomationAction`
- `CreateProjectTaskAutomationAction`
- `CreateProjectDriveFolderAction`
- `CreateProjectChannelAction`
- `OpenExtensionWorkspaceAction`

---

## Comment ajouter une nouvelle app ou une nouvelle automation

## Cas A - Ajouter un nouvel événement métier

Exemple : `meeting_created`

Étapes :

1. créer un event Laravel qui implémente `AutomationContextEvent`
2. dispatcher cet event après succès métier
3. enregistrer un provider dans `automation.php`
4. créer les suggestions adaptées

## Cas B - Ajouter une nouvelle suggestion

Exemple : `create_google_meet_room`

Étapes :

1. créer ou modifier un `SuggestionProvider`
2. retourner une `SuggestionDefinition`
3. ajouter les métadonnées utiles :
   - `integration`
   - `installed`
   - `target_url`

## Cas C - Ajouter une nouvelle action réelle

Exemple : `sync_to_google_sheets`

Étapes :

1. créer une classe d'action dans `packages/vendor/automation/src/Actions`
2. implémenter `AutomationAction`
3. vérifier extension, tenant et données source
4. enregistrer l'action dans `automation.php`

---

## Bonnes pratiques à respecter

- ne jamais contourner le moteur depuis un controller
- ne jamais exécuter une intégration externe sans vérifier le tenant
- toujours vérifier si l'app cible est installée
- toujours journaliser le résultat
- garder les providers petits et lisibles
- garder les actions concentrées sur une seule responsabilité

---

## Limites actuelles

Le système est déjà opérationnel, mais il reste des extensions possibles :

- historique visuel complet des automations
- retry manuel d'une automation échouée
- édition avancée d'une suggestion avant acceptation
- automation builder visuel type Zapier / Notion / Asana
- règles plus avancées par secteur, équipe ou rôle

---

## Conclusion

Le CRM possède maintenant un moteur d'automation central qui permet aux apps de collaborer sans se coupler directement entre elles.

La logique métier devient :

- les modules publient des événements
- le moteur propose
- l'utilisateur décide
- le moteur exécute

Cela permet d'ajouter de nouvelles apps et de nouvelles automatisations sans réécrire le coeur du CRM.
