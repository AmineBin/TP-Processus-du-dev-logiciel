# Demandes organisateur et validation administrative

## Périmètre

Ce backend utilise la table **`demande_inscription`** comme file d’attente des
futurs organisateurs. Il reprend la structure SQL proposée par Élie dans
`feature/database-schema`, sans créer de compte utilisateur au dépôt.

Les routes sont déclarées dans **`routes/web.php`**, sans préfixe `/api/v1` et sans
nouveau système d’authentification. Elles utilisent les sessions Laravel et le
CSRF existants. Les réponses sont en JSON pour le futur formulaire et le panel
admin, même si le client n’envoie pas `Accept: application/json`.

Aucun formulaire HTML, panel admin, changement de rôle à la connexion, notification
mail ou approbation de comptes administrateurs n’est ajouté ici. Les autres
besoins du Sprint (informations de tournoi, filtrage et exclusion) restent distincts.

## Installation et schéma

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

Ne pas exécuter le `CREATE TABLE demande_inscription` SQL puis la migration sur
la même base. Si une table a déjà été créée manuellement sur une autre machine,
convenir avec le responsable BDD de sa reprise avant de migrer ; ne pas la supprimer
pour contourner l’erreur. La migration n’efface aucune table existante lors de son
application. Son rollback supprime la nouvelle table et ses demandes : à réserver
aux environnements et procédures où cette perte est voulue.

La migration conserve les champs métier du SQL : identité, hachage, rôle, statut,
date de création, date de traitement, administrateur et motif de refus. Elle ajoute :

- `utilisateur_cree_id` : référence au compte créé après approbation ;
- `email_en_attente` et `pseudo_en_attente` : colonnes calculées et indexées uniques,
  renseignées par la BDD seulement lorsque le statut est `EN_ATTENTE` ;
- un index de file d’attente sur statut, date et ID.

Ces deux clés techniques empêchent les demandes simultanées pour une même identité.
Elles deviennent `NULL` après traitement : un ancien refus n’interdit pas une
nouvelle candidature. Les identités sont normalisées/comparées sans casse ; les
collations de la BDD restent applicables. Les colonnes techniques ne sont pas
modifiables par le client et ne sont pas renvoyées dans les réponses.

## Cycle de vie et sécurité

1. Le visiteur remplit une demande avec ses identifiants et données personnelles.
2. Le rôle demandé est fixé à `ORGANISATEUR` côté serveur, par son code métier.
3. Le mot de passe est immédiatement haché. Aucun compte ni session connectée n’est créé.
4. L’administrateur actif consulte la liste en attente.
5. Il approuve ou refuse une demande encore `EN_ATTENTE`, dans une transaction.

### Approbation

- Verrouiller la demande et refuser un second traitement.
- Vérifier le rôle demandé, la disponibilité des identifiants et les informations nécessaires au compte.
- Créer un nouvel utilisateur actif, avec le rôle `ORGANISATEUR` recherché par code.
- **Copier le hachage existant sans rehacher le mot de passe.**
- Passer la demande à `APPROUVEE`, enregistrer la date, l’admin et l’ID du compte créé.

### Refus

- Motif obligatoire, au maximum 500 caractères.
- Passer la demande à `REFUSEE`, enregistrer la date et l’administrateur.
- Ne créer aucun utilisateur et ne modifier aucun compte existant.

Après approbation ou refus, la copie du hachage est effacée de la demande : le
champ non nullable contient une chaîne vide. Le suivi administratif reste conservé.
Après approbation, le seul hachage utile est celui du compte `utilisateur`.

Un compte créé entre le dépôt et l’approbation bloque l’opération avec un conflit.
Il n’est jamais écrasé ou promu automatiquement. Le rôle `ADMIN` n’est jamais
accessible depuis le formulaire public. Le dépôt de préinscription est réservé
aux visiteurs : la promotion d’un compte joueur existant n’est pas ce parcours.

Il n’y a pas d’accès public à la liste ou au détail des demandes, ni de recherche
publique par email. Le candidat ne peut pas se connecter avant l’approbation.
La vérification d’adresse email et les notifications restent hors périmètre.

## Endpoints

Base locale d’exemple : `http://localhost:8000`.

| Méthode | URL | Accès | Succès |
| --- | --- | --- | --- |
| POST | `/organizer-requests` | Visiteur, CSRF | 201, demande enregistrée |
| GET | `/admin/organizer-requests` | Admin actif connecté | 200, liste paginée |
| GET | `/admin/organizer-requests/{id}` | Admin actif connecté | 200, détail |
| POST | `/admin/organizer-requests/{id}/approve` | Admin actif, CSRF | 200, compte créé |
| POST | `/admin/organizer-requests/{id}/reject` | Admin actif, CSRF | 200, refus enregistré |

Le rôle administrateur est lu depuis le compte connecté. Un joueur ou un
organisateur reçoit `ADMIN_REQUIRED` (403), même s’il connaît l’ID d’une demande.

### Déposer une demande

Corps JSON ou données de formulaire :

```json
{
  "username": "camille_organise",
  "email": "camille.organisation@example.test",
  "first_name": "Camille",
  "last_name": "Dupont",
  "phone": "0612345678",
  "birth_date": "2000-01-01",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

| Champ | Validation |
| --- | --- |
| `username` | Obligatoire, maximum 50 caractères, absent des comptes et demandes en attente. |
| `email` | Adresse valide, maximum 100 caractères, normalisée en minuscules, mêmes vérifications d’unicité. |
| `first_name`, `last_name` | Facultatifs, maximum 50 caractères chacun. |
| `phone` | Obligatoire, chaîne d’au maximum 10 caractères. |
| `birth_date` | Date valide strictement antérieure à aujourd’hui. |
| `password` | Chaîne d’au moins 8 caractères, confirmée ; maximum 72 octets avec bcrypt. |
| `password_confirmation` | Identique au mot de passe ; jamais stockée. |

Ne pas envoyer `role`, `role_id`, `statut`, `status`, `traite_par`,
`date_traitement`, `utilisateur_cree_id` ou `mot_de_passe_hash` : ces champs de
traitement sont interdits. Le client ne choisit aucun privilège.

Réponse 201 :

```json
{
  "message": "Demande enregistrée, en attente de validation par un administrateur.",
  "data": { "id": 1, "status": "EN_ATTENTE" }
}
```

L’ID est un exemple. Aucun hachage ou secret n’apparaît dans la réponse.
La limite est de **six tentatives par minute et par IP**, y compris les saisies
invalides ; une septième renvoie 429 avec `Retry-After`. Ce compteur est indépendant
de celui de la connexion : un dépôt invalide ne consomme pas le quota de `/login`.

### Lister et consulter

```text
GET /admin/organizer-requests
GET /admin/organizer-requests?status=REFUSEE&page=1&per_page=20
GET /admin/organizer-requests/1
```

- Filtre `status` : `EN_ATTENTE` par défaut, ou `APPROUVEE`, `REFUSEE`.
- Pagination `page >= 1`, `per_page` de 1 à 100, valeur par défaut 20.
- Ordre : date de création décroissante, puis ID décroissant.
- La liste concerne les demandes du rôle `ORGANISATEUR` seulement.
- Réponse de liste Laravel : `data`, `links`, `meta` ; le détail contient `data`.

Champs exposés à l’administrateur pour chaque demande :

```json
{
  "id": 1,
  "username": "camille_organise",
  "email": "camille.organisation@example.test",
  "first_name": "Camille",
  "last_name": "Dupont",
  "phone": "0612345678",
  "birth_date": "2000-01-01",
  "requested_role": "ORGANISATEUR",
  "status": "EN_ATTENTE",
  "submitted_at": "2026-11-09T10:00:00+00:00",
  "reviewed_at": null,
  "reviewed_by": null,
  "rejection_reason": null,
  "created_user_id": null
}
```

Après traitement, `reviewed_by` contient uniquement `id` et `username` de l’admin.
Les références peuvent devenir nulles si un compte est supprimé ; les clés étrangères
utilisent `ON DELETE SET NULL` pour conserver la demande historique.

### Approuver ou refuser

```text
POST /admin/organizer-requests/1/approve
Corps : {}
```

Réponse 200 : `message` et `data` au format ci-dessus, avec `status = APPROUVEE`,
`reviewed_at`, `reviewed_by` et `created_user_id`. Le nouveau compte peut ensuite
utiliser le formulaire `/login` avec son mot de passe initial, puis `/tournament`.

```text
POST /admin/organizer-requests/1/reject
Corps : { "reason": "Merci de compléter les informations de contact." }
```

Réponse 200 : `status = REFUSEE`, motif, date et admin renseignés ; aucun compte créé.
Une demande traitée n’est pas remise en attente : une nouvelle candidature après
refus reçoit un nouvel ID et conserve l’ancien refus dans l’historique.

## Brancher le frontend existant

Conserver les cookies de session. Pour le panel, se connecter comme admin avec le
parcours `/login` déjà présent. Pour le formulaire public, utiliser une session
visiteur, pas la session d’un joueur ou d’un administrateur.

Dans la future vue Blade qui enverra ces requêtes, exposer le jeton :

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

Exemple JavaScript pour une action du panel :

```javascript
const response = await fetch(`/admin/organizer-requests/${requestId}/approve`, {
  method: 'POST',
  credentials: 'same-origin',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
  },
  body: JSON.stringify({}),
});
const result = await response.json();
if (!response.ok) {
  // Utiliser result.code et result.errors pour piloter l’affichage.
} else {
  // Retirer ou recharger la demande traitée dans la liste en attente.
}
```

Un POST HTML classique peut aussi envoyer `@csrf` ; la réponse reste JSON.
Ne pas désactiver le CSRF pour raccorder le frontend. Après déconnexion ou session
expirée, recharger la page et le jeton avant de réessayer. Une application sur une
autre origine demanderait une configuration d’authentification/CORS spécifique,
non ajoutée dans ce lot. Aucun JWT, Sanctum ou préfixe d’API n’est introduit.
Afficher les chaînes reçues avec un template échappé ou `textContent`, jamais en
injectant les noms, emails ou motifs via `innerHTML`.

## Erreurs

Format commun : `code`, `message`, et éventuellement `errors` classées par champ.

| HTTP | Code | Situation |
| --- | --- | --- |
| 401 | `AUTH_REQUIRED` | Pas de session connectée sur une action admin. |
| 403 | `AUTH_INACTIVE` | Compte connecté désactivé ; sa session est révoquée. |
| 403 | `ADMIN_REQUIRED` | Rôle non administrateur. |
| 404 | `ORGANIZER_REQUEST_NOT_FOUND` | Demande introuvable ou non organisateur pour le détail. |
| 409 | `AUTH_ALREADY_AUTHENTICATED` | Dépôt public tenté depuis une session connectée. |
| 409 | `ORGANIZER_REQUEST_CONFLICT` | Collision concurrente entre demandes en attente. |
| 409 | `ORGANIZER_REQUEST_ACCOUNT_CONFLICT` | Email/pseudo déjà utilisé au moment de l’approbation. |
| 409 | `ORGANIZER_REQUEST_ALREADY_PROCESSED` | Demande déjà approuvée ou refusée. |
| 409 | `ORGANIZER_REQUEST_INVALID_ROLE` | Une demande importée vise un rôle autre qu’organisateur. |
| 409 | `ORGANIZER_REQUEST_INCOMPLETE` | Demande SQL importée sans les données nécessaires ou avec un faux hachage. |
| 419 | `CSRF_TOKEN_MISMATCH` | Jeton manquant, incorrect ou expiré. |
| 422 | `VALIDATION_FAILED` | Saisie invalide, identité prise, motif absent, filtre invalide ou champ interdit. |
| 429 | `ORGANIZER_REQUEST_RATE_LIMITED` | Limite de dépôt atteinte ; consulter `Retry-After`. |
| 503 | `ORGANIZER_REQUEST_ROLE_UNAVAILABLE` | Référentiel `ORGANISATEUR` indisponible. |
| 503 | `ORGANIZER_REQUEST_FAILED` | Erreur SQL de dépôt, validation ou traitement, sans détail technique public. |

Les identités déjà présentes détectées pendant la validation renvoient 422.
Le 409 couvre notamment une collision intervenant après cette vérification.
Les autres erreurs Laravel, par exemple une méthode HTTP non autorisée, conservent
leur fonctionnement normal.

## Organisation du code et preuves

- `StoreOrganizerRequest`, `ListOrganizerRequestsRequest`, `RejectOrganizerRequest` : contrats d’entrée.
- `AvailableOrganizerIdentity` : vérification des comptes et de la file en attente.
- `OrganizerRequestController` : dépôt public uniquement.
- `Admin/OrganizerRequestController` : liste, détail et adaptation des réponses de traitement.
- Actions `ApproveOrganizerRequest` / `RejectOrganizerRequest` : transactions et règles métier, avec contrôle admin également pour les appels hors HTTP.
- `OrganizerRequestResource` : liste blanche des champs du panel, sans secret.
- `OrganizerRequest` : table, statuts, dates et relations de suivi.
- `EnsureAdmin`, `ForceJsonResponse` : accès administratif et format des réponses.

```bash
php artisan test
php artisan test --filter=OrganizerApprovalTest
php artisan route:list --path=organizer-requests -v
```

Les tests isolent la BDD dans SQLite en mémoire : dépôt, validations, privilèges,
confidentialité, liste/pagination, approbation et connexion, refus et nouvelle
demande, contraintes de doublon, double traitement, conflits de compte, imports
incomplets, erreurs SQL, rollback, limites de fréquence et URLs sans préfixe.
La migration est aussi testée en aller-retour avec le schéma existant.

Les verrous de ligne et contraintes protègent les traitements passant par ces
actions. Les tests SQLite ne prouvent pas une charge concurrente MySQL réelle.
Les méthodes publiques sont documentées en PHPDoc ; aucune mesure de couverture
de lignes ni validation du PO n’est revendiquée.
