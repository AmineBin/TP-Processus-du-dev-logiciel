# Catalogue des erreurs applicatives

## Utilisation

Importer `App\Support\ErrorHandler`, puis utiliser
`ErrorHandler::message(ErrorHandler::AUTH_REQUIRED)` pour résoudre un code en
message. `ErrorHandler::all()` retourne le catalogue complet sans accès à la base.
Un code inconnu utilise le texte de `UNKNOWN_ERROR`.

`ErrorHandler::respond()` reçoit la requête, le code, le statut HTTP, le nom de route
de retour et, facultativement, les erreurs par champ et les anciennes saisies
explicitement autorisées. Les mots de passe ne doivent jamais être passés comme
anciennes saisies. Pour une route de détail, `routeParameters` transmet ses
paramètres, par exemple `['id' => $id]` pour `tournament.registration`.

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
| `TOURNAMENT_FORBIDDEN` | 403 | Seuls les organisateurs peuvent créer un tournoi. Retour à l’accueil en HTML. |
| `TOURNAMENT_CREATION_FAILED` | 503 | Le tournoi n’a pas pu être enregistré. Réessaie plus tard. Retour au formulaire avec anciennes saisies en HTML. |
| `TOURNAMENT_ALREADY_REGISTERED` | 409 | Tu es déjà inscrit à ce tournoi. Participation en attente ou confirmée. |
| `TOURNAMENT_REGISTRATION_CLOSED` | 409 | La date limite d’inscription est dépassée. |
| `TOURNAMENT_FULL` | 409 | Désolé, ce tournoi est complet. Seules les participations actives réservent une place. |
| `TOURNAMENT_REGISTRATION_UNAVAILABLE` | 503 | Les inscriptions sont temporairement indisponibles. Réessaie plus tard. Référentiel absent ou état non réactivable. |
| `TOURNAMENT_REGISTRATION_FAILED` | 503 | L’inscription n’a pas pu être enregistrée. Réessaie plus tard. Transaction annulée, détail SQL journalisé. |
| `ADMIN_REQUIRED` | 403 | Cette action est réservée aux administrateurs. |
| `ORGANIZER_REQUEST_NOT_FOUND` | 404 | Demande introuvable. |
| `ORGANIZER_REQUEST_CONFLICT` | 409 | Une demande en attente utilise déjà cette adresse e-mail ou ce pseudo. |
| `ORGANIZER_REQUEST_ACCOUNT_CONFLICT` | 409 | Un compte utilise déjà cette identité. Aucun compte existant n’a été modifié. |
| `ORGANIZER_REQUEST_ALREADY_PROCESSED` | 409 | Cette demande a déjà été traitée. |
| `ORGANIZER_REQUEST_INVALID_ROLE` | 409 | Cette demande ne concerne pas le rôle organisateur. |
| `ORGANIZER_REQUEST_INCOMPLETE` | 409 | Les informations de cette demande ne permettent pas de créer un compte valide. |
| `CSRF_TOKEN_MISMATCH` | 419 | La session a expiré. Recharge le formulaire puis réessaie. Contrat du module de demandes organisateur. |
| `ORGANIZER_REQUEST_RATE_LIMITED` | 429 | Trop de demandes. Réessaie dans une minute. |
| `ORGANIZER_REQUEST_ROLE_UNAVAILABLE` | 503 | Les demandes organisateur sont temporairement indisponibles. |
| `ORGANIZER_REQUEST_FAILED` | 503 | La demande n’a pas pu être enregistrée ou traitée. Réessaie plus tard. |
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
| `FIELD_INTEGER` | Le champ :attribute doit être un nombre entier. |
| `FIELD_MIN_NUMBER` | Le champ :attribute doit être supérieur ou égal à :min. |
| `FIELD_MAX_NUMBER` | Le champ :attribute ne doit pas dépasser :max. |
| `TOURNAMENT_END_AFTER_START` | La date de fin doit être postérieure à la date de début. |
| `TOURNAMENT_DEADLINE_BEFORE_START` | La date limite d’inscription ne doit pas dépasser la date de début. |
| `TOURNAMENT_INVALID_CATEGORY` | Choisis une discipline valide, pas une catégorie racine ou intermédiaire. |
| `TOURNAMENT_INVALID_STATUS` | Choisis un statut de tournoi valide. |
| `BIRTH_DATE_PAST` | La date de naissance doit être dans le passé. |
| `PASSWORD_CONFIRMATION` | Les deux mots de passe ne correspondent pas. |
| `EMAIL_TAKEN` | Cette adresse e-mail possède déjà un compte. |
| `USERNAME_TAKEN` | Ce pseudo est déjà utilisé. |
| `PASSWORD_TOO_LONG` | Le mot de passe ne doit pas dépasser 72 octets avec bcrypt. |
| `FIELD_PROHIBITED` | Le champ :attribute ne doit pas être fourni. |
| `ORGANIZER_INVALID_STATUS` | Le statut doit être EN_ATTENTE, APPROUVEE ou REFUSEE. |

## Limites et exploitation

Ce catalogue n’est pas un remplacement du gestionnaire d’exceptions Laravel.
Le module [demandes organisateur](organizer-approval.md) adapte ses erreurs CSRF
et demandes introuvables au catalogue JSON. Ailleurs, les erreurs CSRF (419),
routes introuvables (404), validations ne passant pas par `ValidatedRequest` et
autres exceptions non traitées conservent le fonctionnement Laravel. La création de tournois et leurs inscriptions utilisent ce catalogue,
avec retour au formulaire ou au détail pour les réponses HTML.

Le contrôleur ne renvoie pas les requêtes SQL, hachages ou traces des exceptions
interceptées pendant la création du compte, du tournoi ou d’une participation.
Les erreurs techniques sont journalisées
par Laravel ; l’accès aux logs doit être restreint car ils peuvent contenir des
détails sensibles. Utiliser `APP_DEBUG=false` hors développement pour les autres
exceptions et ne jamais publier les logs ou la configuration privée.

Pour réparer un rôle absent sans toucher aux utilisateurs :
`php artisan db:seed --class=RoleSeeder`.
Voir [le diagnostic de clé étrangère](authentication.md#corriger-lerreur-de-clé-étrangère-fk_utilisateur_role).
