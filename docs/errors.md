# Catalogue des erreurs d’authentification

## Utilisation

Importer `App\Support\ErrorHandler`, puis utiliser
`ErrorHandler::message(ErrorHandler::AUTH_REQUIRED)` pour résoudre un code en
message. `ErrorHandler::all()` retourne le catalogue complet sans accès à la base.
Un code inconnu utilise le texte de `UNKNOWN_ERROR`.

`ErrorHandler::respond()` reçoit la requête, le code, le statut HTTP, le nom de route
de retour et, facultativement, les erreurs par champ et les anciennes saisies
explicitement autorisées. Les mots de passe ne doivent jamais être passés à ce
dernier paramètre.

- Client JSON : objet `code`, `message`, et éventuellement `errors` ; statut indiqué ci-dessous.
- Client HTML : redirection HTTP 302, `error_code` en session et message dans `$errors` ou `session('error')`.

Les statuts de la table concernent le JSON, pas les redirections HTML.

## Codes applicatifs

| Code | HTTP JSON | Message / situation |
| --- | --- | --- |
| `AUTH_REQUIRED` | 401 | Connecte-toi pour accéder à cette page. |
| `AUTH_INVALID_CREDENTIALS` | 401 | Adresse e-mail ou mot de passe incorrect. Compte inexistant ou désactivé compris. |
| `AUTH_INACTIVE` | 403 | Ton compte est désactivé. La session existante est révoquée. |
| `AUTH_ALREADY_AUTHENTICATED` | 409 | Tu es déjà connecté. Accès JSON aux routes réservées aux visiteurs. |
| `AUTH_ROLE_UNAVAILABLE` | 503 | La création de compte est temporairement indisponible. Réessaie plus tard. Rôle `UTILISATEUR` absent. |
| `AUTH_REGISTRATION_FAILED` | 503 | Impossible de créer le compte pour le moment. Réessaie plus tard. Erreur SQL pendant la création. |
| `AUTH_ACCOUNT_CONFLICT` | 409 | Un compte utilise déjà cette adresse e-mail ou ce pseudo. Conflit SQL concurrent. |
| `AUTH_RATE_LIMITED` | 429 | Trop de tentatives de connexion. Réessaie dans une minute. Le header `Retry-After` précise le délai. |
| `VALIDATION_FAILED` | 422 | Certains champs sont invalides. `errors` détaille les champs concernés. |
| `UNKNOWN_ERROR` | — | Une erreur est survenue. Réessaie plus tard. Repli du catalogue, sans statut implicite. |

Les doublons détectés par `RegisterRequest` sont des validations HTTP 422. Le code
HTTP 409 couvre le conflit concurrent détecté par la contrainte SQL après cette validation.

## Messages de validation

Ces codes sont des modèles de messages utilisés à l’intérieur des erreurs par
champ. Le code global de la réponse reste `VALIDATION_FAILED`.
Laravel remplace les paramètres `:attribute`, `:min` et `:max`.

| Code | Message |
| --- | --- |
| `FIELD_REQUIRED` | Le champ :attribute est obligatoire. |
| `FIELD_STRING` | Le champ :attribute doit être une chaîne de caractères. |
| `FIELD_EMAIL` | L’adresse e-mail doit être valide. |
| `FIELD_MAX` | Le champ :attribute ne doit pas dépasser :max caractères. |
| `FIELD_MIN` | Le champ :attribute doit contenir au moins :min caractères. |
| `FIELD_DATE` | Le champ :attribute doit être une date valide. |
| `BIRTH_DATE_PAST` | La date de naissance doit être dans le passé. |
| `PASSWORD_CONFIRMATION` | Les deux mots de passe ne correspondent pas. |
| `EMAIL_TAKEN` | Cette adresse e-mail possède déjà un compte. |
| `USERNAME_TAKEN` | Ce pseudo est déjà utilisé. |

## Limites et exploitation

Ce catalogue n’est pas un remplacement du gestionnaire d’exceptions Laravel.
Les erreurs CSRF (419), routes introuvables (404), erreurs de validation hors auth
et autres exceptions non traitées conservent le fonctionnement Laravel. Les erreurs
métier des tournois ne sont pas encore intégrées à ce catalogue.

Le contrôleur ne renvoie pas les requêtes SQL, hachages ou traces des exceptions
interceptées pendant la création du compte. Les erreurs techniques sont journalisées
par Laravel ; l’accès aux logs doit être restreint car ils peuvent contenir des
détails sensibles. Utiliser `APP_DEBUG=false` hors développement pour les autres
exceptions et ne jamais publier les logs ou la configuration privée.

Pour réparer un rôle absent sans toucher aux utilisateurs :
`php artisan db:seed --class=RoleSeeder`.
Voir [le diagnostic de clé étrangère](authentication.md#corriger-lerreur-de-clé-étrangère-fk_utilisateur_role).
