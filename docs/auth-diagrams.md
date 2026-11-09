# Authentification — schémas Mermaid

Ces schémas décrivent le code actuel, pas une architecture future. Ils sont
consultables directement sur GitHub ou dans un lecteur Markdown compatible Mermaid.
L’authentification repose sur le cookie et la session Laravel, même en JSON.

## 1. Carte des fichiers et responsabilités

Les flèches indiquent les liens entre composants. L’ordre réel d’une requête est
détaillé dans les schémas suivants : une `FormRequest` valide les données **avant**
l’exécution de l’action du contrôleur.

```mermaid
flowchart TB
    Front["resources/views/login.blade.php<br/>resources/views/register.blade.php<br/>Formulaires, CSRF et affichage des messages"]
    Routes["routes/web.php<br/>URL, noms de routes et actions"]
    Bootstrap["bootstrap/app.php<br/>Alias des middlewares et erreur de limitation"]

    subgraph Access["app/Http/Middleware — accès"]
        Active["EnsureActiveAccount.php<br/>Révoquer les comptes désactivés"]
        Auth["Authenticate.php<br/>Exiger une session connectée"]
        Guest["RedirectIfAuthenticated.php<br/>Réserver les formulaires aux visiteurs"]
    end

    subgraph Validation["app/Http/Requests — validation"]
        BaseRequest["AuthRequest.php<br/>Messages et anciennes saisies propres à l’auth"]
        SharedRequest["ValidatedRequest.php<br/>Contrat de validation partagé avec les tournois"]
        BaseRequest -. "hérite de" .-> SharedRequest
        Login["LoginRequest.php<br/>Règles de connexion"]
        Register["RegisterRequest.php<br/>Règles de création de compte"]
        Login -. "hérite de" .-> BaseRequest
        Register -. "hérite de" .-> BaseRequest
    end

    Controller["app/Http/Controllers/UserController.php<br/>Formulaires, connexion, inscription et déconnexion"]
    User["app/Models/User.php<br/>Utilisateur authentifiable et relation au rôle"]
    Role["app/Models/Role.php<br/>Codes et données des rôles"]
    DB[("BDD<br/>utilisateur et role_utilisateur")]
    Errors["app/Support/ErrorHandler.php<br/>Code stable vers message public et réponse"]
    Seeder["database/seeders/RoleSeeder.php<br/>Initialisation des rôles, hors requête HTTP"]

    Front --> Routes
    Bootstrap -. "enregistre les alias" .-> Active
    Bootstrap -. "enregistre les alias" .-> Auth
    Bootstrap -. "enregistre les alias" .-> Guest
    Bootstrap -. "erreur 429 de connexion" .-> Errors
    Routes --> Active
    Active --> Auth
    Active --> Guest
    Routes -. "désigne les actions" .-> Controller
    Controller -. "injection et validation préalable" .-> Login
    Controller -. "injection et validation préalable" .-> Register
    Controller --> User
    Controller --> Role
    User --> DB
    Role --> DB
    Seeder --> Role
    Active -. "erreur" .-> Errors
    Auth -. "erreur" .-> Errors
    Guest -. "erreur JSON" .-> Errors
    SharedRequest -. "validation invalide" .-> Errors
    Controller -. "erreur métier" .-> Errors
    Errors --> Front
```

**À retenir :** les routes ne font pas de SQL ; les middlewares contrôlent l’accès ;
les Requests valident ; le contrôleur orchestre ; les modèles accèdent aux données.
`ErrorHandler` ne consulte pas la BDD.

## 2. Contrôles d’accès : active, guest et auth

```mermaid
flowchart TD
    Start["Requête vers une route web"] --> Web["Groupe web Laravel<br/>Session et vérification CSRF pour les POST"]
    Web --> Active["active : EnsureActiveAccount"]
    Active --> Disabled{"Session connectée<br/>et compte désactivé ?"}
    Disabled -->|Oui| Revoke["Auth::logout<br/>Invalider la session<br/>Renouveler le jeton CSRF"]
    Revoke --> Inactive["ErrorHandler<br/>AUTH_INACTIVE : JSON 403 ou redirection login"]
    Disabled -->|Non| Kind{"Type de route"}

    Kind -->|GET /| Root["UserController::index<br/>Redirection login ou home"]
    Kind -->|login ou register| Guest["guest : RedirectIfAuthenticated"]
    Guest --> Connected{"Utilisateur connecté ?"}
    Connected -->|Oui, HTML| Home["Redirection vers home"]
    Connected -->|Oui, JSON| Already["ErrorHandler<br/>AUTH_ALREADY_AUTHENTICATED : 409"]
    Connected -->|Non| Throttle{"POST /login ?"}
    Throttle -->|Oui| Limit["throttle:6,1<br/>Maximum 6 requêtes par minute et par IP"]
    Limit --> Allowed{"Limite respectée ?"}
    Allowed -->|Non| RateError["bootstrap/app.php et ErrorHandler<br/>AUTH_RATE_LIMITED : JSON 429 ou redirection login"]
    Allowed -->|Oui| Action["Validation du POST puis action du contrôleur<br/>Pour un GET : affichage du formulaire"]
    Throttle -->|Non| Action

    Kind -->|home, sport, tournoi ou logout| Auth["auth : Authenticate"]
    Auth --> Session{"Utilisateur connecté ?"}
    Session -->|Oui| Protected["Action du contrôleur concerné"]
    Session -->|Non| Intended["Pour un GET HTML seulement :<br/>mémoriser la page à reprendre après connexion"]
    Intended --> Required["ErrorHandler<br/>AUTH_REQUIRED : JSON 401 ou redirection login"]
```

`active` et `auth` n’ont pas la même fonction : le premier vérifie un compte déjà
connecté ; le second exige qu’une connexion existe. Un compte désactivé tentant de
se connecter est refusé dans `Auth::attempt()`, avec le message générique des
identifiants incorrects. Les échecs CSRF restent gérés par Laravel, hors catalogue.

## 3. Connexion : du formulaire à la session

Le parcours ci-dessous suppose que les middlewares ont autorisé la requête.

```mermaid
sequenceDiagram
    autonumber
    participant F as Frontend
    participant R as LoginRequest / AuthRequest
    participant C as UserController
    participant A as Auth Laravel
    participant U as User / BDD
    participant S as Session
    participant E as ErrorHandler

    F->>R: POST /login : email, password et CSRF
    R->>R: Valider les champs et leurs types
    alt Données invalides
        R->>E: VALIDATION_FAILED et erreurs par champ
        E-->>F: JSON 422 ou redirection login avec erreurs
    else Données valides
        R->>C: login avec données validées
        C->>A: Auth::attempt avec est_actif = true
        A->>U: Charger le compte et vérifier mot_de_passe_hash
        U-->>A: Résultat de la vérification
        alt Compte absent, désactivé ou mot de passe incorrect
            A-->>C: Échec
            C->>E: AUTH_INVALID_CREDENTIALS
            E-->>F: JSON 401 ou redirection login avec erreur email
        else Authentification réussie
            A->>S: Enregistrer l’utilisateur authentifié
            A-->>C: Succès
            C->>S: Régénérer la session
            C-->>F: JSON 200 ou redirection vers la page prévue / home
        end
    end
```

`User` indique à Laravel le nom du champ de hachage : `mot_de_passe_hash`.
Le mot de passe n’est jamais comparé en clair dans une requête SQL. La régénération
protège contre la fixation de session ; l’ancienne page POST n’est jamais rejouée.

## 4. Inscription : validation, rôle et sauvegarde

Ce parcours distingue la validation préalable et l’insertion protégée par les
contraintes SQL. Le frontend ne choisit ni le rôle ni l’état actif du compte.

```mermaid
sequenceDiagram
    autonumber
    participant F as Frontend
    participant R as RegisterRequest / AuthRequest
    participant C as UserController
    participant Role as Modèle Role
    participant U as Modèle User
    participant DB as BDD
    participant E as ErrorHandler

    F->>R: POST /register : champs du formulaire et CSRF
    R->>R: Vérifier formats, date passée et confirmation
    R->>DB: Vérifier l’unicité email et nom_utilisateur si applicable
    DB-->>R: Résultat de validation d’unicité
    alt Données invalides
        R->>E: VALIDATION_FAILED et erreurs par champ
        E-->>F: JSON 422 ou redirection register avec saisies non sensibles
    else Données valides
        R->>C: register avec données validées
        C->>Role: Rechercher code = UTILISATEUR
        Role->>DB: Lire role_utilisateur
        DB-->>Role: Rôle trouvé ou absent
        Role-->>C: Modèle Role ou null
        alt Rôle absent
            C->>E: AUTH_ROLE_UNAVAILABLE
            E-->>F: JSON 503 ou redirection register
        else Rôle présent
            C->>C: Hash::make du mot de passe
            C->>U: Créer le modèle et associer le rôle trouvé
            U->>DB: Insérer utilisateur avec le véritable role_id
            alt Enregistrement réussi
                DB-->>U: Utilisateur enregistré
                U-->>C: Succès
                C-->>F: JSON 201 ou redirection login avec confirmation
            else Conflit concurrent sur une valeur unique
                DB-->>C: UniqueConstraintViolationException via Eloquent
                C->>E: AUTH_ACCOUNT_CONFLICT
                E-->>F: JSON 409 ou redirection register
            else Autre erreur SQL pendant la création
                DB-->>C: QueryException via Eloquent
                C->>C: report de l’exception dans les logs
                C->>E: AUTH_REGISTRATION_FAILED
                E-->>F: JSON 503 ou redirection register, sans détails SQL
            end
        end
    end
```

La création de compte ne connecte pas automatiquement l’utilisateur. Les erreurs
SQL interceptées dans ce schéma sont celles du bloc de création du contrôleur ;
`ErrorHandler` n’est pas un gestionnaire global de toutes les exceptions Laravel.

## 5. ErrorHandler : du code au message frontend

```mermaid
flowchart TD
    Middleware["Middleware<br/>AUTH_REQUIRED, AUTH_INACTIVE, etc."] --> Handler
    Request["ValidatedRequest<br/>VALIDATION_FAILED et erreurs par champ"] --> Handler
    Controller["UserController<br/>Identifiants incorrects, rôle absent, conflit, erreur SQL"] --> Handler
    Bootstrap["bootstrap/app.php<br/>Limitation des tentatives de connexion"] --> Handler

    Handler["ErrorHandler::respond"] --> Catalogue["ErrorHandler::message<br/>Recherche dans MESSAGES"]
    Catalogue --> Known{"Code connu ?"}
    Known -->|Oui| Message["Message public correspondant"]
    Known -->|Non| Fallback["Message de repli UNKNOWN_ERROR"]
    Message --> Format{"Le client attend du JSON ?"}
    Fallback --> Format

    Format -->|Oui| Json["Statut HTTP fourni par l’appelant<br/>code et message<br/>errors si des champs sont invalides"]
    Format -->|Non| Redirect["Redirection HTTP 302 vers le formulaire<br/>error_code en session"]
    Redirect --> Fields{"Erreurs par champ fournies ?"}
    Fields -->|Oui| Bag["withErrors<br/>Affichage via la variable errors de Blade"]
    Fields -->|Non| Flash["Message global en session : error"]
    Bag --> View["Vue Blade : affichage échappé<br/>old pour les saisies explicitement conservées"]
    Flash --> View
```

Le code est stable et sert aux décisions du frontend ; le message est destiné à
l’utilisateur. Les modèles `FIELD_REQUIRED`, `FIELD_MIN`, etc. servent aux messages
de validation ; le code global reste `VALIDATION_FAILED`.
Le statut d’une erreur JSON ne devient pas le statut de sa redirection HTML.

## 6. Modèles User et Role : ce que la BDD garantit

Seuls les champs utiles à cette explication sont représentés.

```mermaid
erDiagram
    ROLE_UTILISATEUR ||--o{ UTILISATEUR : "est attribué à"

    ROLE_UTILISATEUR {
        int id PK "Identifiant technique auto-incrémenté"
        string code UK "UTILISATEUR, ADMIN ou ORGANISATEUR"
        string libelle "Nom affiché"
    }

    UTILISATEUR {
        int id PK
        string nom_utilisateur UK
        string email UK
        string mot_de_passe_hash "Masqué dans la sérialisation du modèle"
        boolean est_actif "true par défaut"
        int role_id FK "Référence role_utilisateur.id"
    }
```

- `app/Models/User.php` correspond à `utilisateur` et déclare `role()` (`belongsTo`).
- `app/Models/Role.php` correspond à `role_utilisateur` et déclare `users()` (`hasMany`).
- L’application recherche le rôle par `code = UTILISATEUR`, puis utilise son ID réel.
  L’ID `1` n’est pas imposé ; les IDs existants ne sont pas renumérotés.
- `RoleSeeder` ajoute les rôles absents ou actualise leurs libellés/descriptions par
  code. **Cela peut modifier les données des rôles**, pas la structure des tables,
  les comptes utilisateurs ou leurs références de rôle existantes.
- Le seeder est une commande d’installation/maintenance : il n’est pas exécuté
  automatiquement à chaque inscription. Si le rôle manque, l’inscription est refusée.

## 7. Déconnexion : fin de la session

```mermaid
sequenceDiagram
    autonumber
    participant F as Frontend
    participant M as Middlewares web / active / auth
    participant C as UserController
    participant A as Auth Laravel
    participant S as Session

    F->>M: POST /logout avec cookie et CSRF
    Note over M: Parcours normal : compte connecté et actif
    M->>C: logout
    C->>A: Auth::logout
    C->>S: Invalider la session et ses données
    C->>S: Renouveler le jeton CSRF
    C-->>F: JSON 200 ou redirection login avec confirmation
    Note over F,S: L’ancien jeton CSRF ne doit plus être réutilisé
```

Si le compte est désactivé, `active` révoque la session avant d’atteindre cette
action et renvoie `AUTH_INACTIVE`. Un visiteur est refusé par `auth`.

## Documents complémentaires

- [Architecture et diagnostic des rôles](authentication.md)
- [Champs, routes et réponses attendus par le frontend](auth-frontend.md)
- [Catalogue complet des erreurs](errors.md)
