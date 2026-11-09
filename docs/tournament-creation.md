# Création de tournois

Cette intégration reprend la création de tournois proposée dans la PR #7 (`amine`)
en conservant les parcours et les conventions de `develop`.
Aucune migration ni remise à zéro de la base n’est nécessaire.

## Routes et accès

| Méthode | URL | Nom | Action |
| --- | --- | --- | --- |
| GET | `/tournament` | `tournaments.create` | Afficher le formulaire. |
| POST | `/tournament` | `tournaments.store` | Valider et enregistrer le tournoi. |

Les deux routes passent par **`active`, `auth`, puis `organizer`**, après les
protections de session et CSRF du groupe `web`.

- Un visiteur est redirigé vers la connexion, ou reçoit `AUTH_REQUIRED` en JSON (401).
- Un compte désactivé perd sa session : `AUTH_INACTIVE` (403 en JSON).
- Un joueur ou un administrateur sans le rôle `ORGANISATEUR` est refusé :
  `TOURNAMENT_FORBIDDEN` (403 en JSON ; redirection vers l’accueil en HTML).
- Seul le rôle dont le **code** est `ORGANISATEUR` autorise la création. Aucun ID
  numérique de rôle n’est imposé. Les rôles administrateur et organisateur restent distincts.
- Le lien « Créer un tournoi » apparaît sur l’accueil des organisateurs uniquement.
  Masquer le lien n’est pas une protection : les deux endpoints vérifient aussi l’autorisation.

## Répartition des responsabilités

| Fichier | Responsabilité |
| --- | --- |
| `routes/web.php` | Déclarer les deux routes nommées sous les groupes communs. Aucun SQL ni traitement d’erreur. |
| `EnsureOrganizer` | Autorisation commune au GET et au POST, via `User::isOrganizer()`. |
| `StoreTournamentRequest` | Valider les champs et conserver une liste blanche des anciennes saisies. |
| `ValidatedRequest` | Partager les messages génériques et la réponse de validation HTML/JSON. |
| `AuthRequest` | Ajouter les messages et anciennes saisies propres à l’authentification ; hérite aussi de `ValidatedRequest`. |
| `TournamentController::create()` | Charger les disciplines et statuts du formulaire. |
| `TournamentController::store()` | Imposer le créateur, enregistrer dans une transaction et retourner la réponse. |
| `Category` | Sélectionner les disciplines feuilles, avec leur parent pour l’affichage. |
| `TournamentStatus` | Lire les statuts de référence. |
| `Tournament` | Enregistrer les champs validés et déclarer les relations au créateur et à la discipline. |
| `ErrorHandler` | Catalogue et réponses des erreurs applicatives. |
| `tournament.blade.php` / `tournament.css` | Formulaire responsive, messages, anciennes saisies et styles isolés. |

Les méthodes existantes `index`, `showRegistration` et `register` sont conservées.
Le modèle `User` conserve son champ de hachage, ses attributs cachés, ses casts et
sa relation au rôle ; l’ancien ajout `getAuthPassword()` n’est pas réintroduit.
`login.css` et `register.css` ne sont pas modifiés par cette fonctionnalité.

## Contrat d’entrée

| Champ | Contraintes |
| --- | --- |
| `nom` | Obligatoire, chaîne, maximum 100 caractères. |
| `description` | Facultative, chaîne, maximum 5 000 caractères. |
| `regles` | Facultatives, chaîne, maximum 5 000 caractères. |
| `date_debut` | Obligatoire, date valide. |
| `date_fin` | Obligatoire, strictement postérieure au début. |
| `date_limite_inscription` | Obligatoire, antérieure ou égale au début. |
| `nombre_max_participants` | Obligatoire, entier de 1 à 2 147 483 647, conforme au type SQL signé. |
| `categorie_id` | Obligatoire, ID d’une discipline feuille, non racine et sans sous-catégories. |
| `statut_id` | Obligatoire, ID existant dans `statut_tournoi`. |

Le formulaire envoie les dates au format `datetime-local` : `YYYY-MM-DDTHH:mm`.
Les statuts proposés sont ceux de la table de référence ; la cohérence des dates
est vérifiée, sans imposer ici une nouvelle politique de transition des statuts.

**Changement volontaire par rapport à la PR #7 :** fin, clôture et capacité ne sont
plus facultatives pour les nouveaux tournois. Les lecteurs existants attendent ces
valeurs ; une capacité `null` entraînait un refus « tournoi complet » dès la première
inscription. Le schéma reste compatible avec les anciennes lignes nullables :
cette correction ne les modifie pas.

Les catégories racines ou intermédiaires sont exclues **du formulaire et de la
validation serveur**. Un tournoi créé dans une discipline apparaît ainsi dans sa
liste. L’organisateur est associé depuis la session : `organisateur_id`, `id`,
les mots de passe ou tout autre champ injecté dans le POST ne sont pas affectés.

## Réponses et erreurs

### HTML / Blade

- Succès : redirection vers la page de la discipline avec `session('success')`.
- Validation : redirection vers `/tournament`, erreurs dans `$errors`,
  `error_code = VALIDATION_FAILED`, anciennes saisies autorisées réaffichées.
- Erreur SQL pendant l’enregistrement : transaction annulée, erreur journalisée,
  retour au formulaire avec `TOURNAMENT_CREATION_FAILED` et anciennes saisies.

Toutes les valeurs et tous les messages sont échappés par Blade. Les champs texte,
textareas et options sélectionnées utilisent `old()` pour éviter de perdre le
formulaire après un échec.

### JSON

Avec `Accept: application/json`, un POST réussi retourne HTTP **201** :

```json
{
  "message": "Tournoi créé avec succès.",
  "data": { "id": 16 },
  "redirect_url": "http://localhost:8000/sport/fps"
}
```

L’ID et l’URL sont des exemples : ils dépendent de la base et de `APP_URL`.
La session et le CSRF restent nécessaires ; ce n’est pas une API sans état.

| Code | HTTP JSON | Situation |
| --- | --- | --- |
| `AUTH_REQUIRED` | 401 | Aucun compte connecté. |
| `AUTH_INACTIVE` | 403 | Compte connecté désactivé. |
| `TOURNAMENT_FORBIDDEN` | 403 | Rôle différent d’organisateur. |
| `VALIDATION_FAILED` | 422 | Champs invalides, avec `errors` par champ. |
| `TOURNAMENT_CREATION_FAILED` | 503 | Erreur SQL pendant la transaction, sans SQL ni trace dans la réponse. |

Les redirections HTML utilisent HTTP 302, pas les statuts JSON de cette table.
Les erreurs CSRF et autres exceptions non interceptées conservent le traitement Laravel.

## Rôles et compte de démonstration

Pour ajouter le rôle sans modifier les utilisateurs :

```bash
php artisan db:seed --class=RoleSeeder
```

Pour disposer aussi du compte organisateur sur une base de développement :

```bash
php artisan db:seed --class=DemoSeeder
```

Se connecter avec `organisateur@example.com` / `password`, puis utiliser le lien
sur `/home`. Le compte n’est créé que si son adresse n’existe pas. Son mot de passe,
son rôle et son état actif ne sont jamais réinitialisés lors d’une réexécution.
Si le compte a été personnalisé, conserver ses identifiants actuels.

Le compte à mot de passe public reste dans `DemoSeeder`, limité à `local` et
`testing`, et non dans le seeder minimal. Les 15 tournois de démonstration sont
rattachés à cet organisateur. Voir [les précautions du seed](demo-data.md).

## Vérifications

```bash
php artisan test
php artisan test --filter=TournamentCreationTest
php artisan route:list -v --except-vendor
```

Les tests utilisent SQLite en mémoire. Ils couvrent notamment :

- accès visiteur, joueur, administrateur et organisateur ; compte désactivé et rôle retiré ;
- rôle organisateur dont l’ID vaut 42 ;
- insertion, créateur imposé, visibilité dans la discipline et inscription d’un joueur ;
- validation des champs, dates, capacité positive et limites du type SQL ;
- exclusion des racines et catégories intermédiaires ;
- restauration des champs et sélections après erreur ; absence d’anciennes saisies injectées ;
- contrat JSON, annulation d’une insertion en cas d’erreur et absence de détails SQL ;
- formulaire sans données de référence et conservation du compte organisateur après un nouveau seed.

Les classes et méthodes publiques ajoutées sont documentées en PHPDoc et leurs
responsabilités sont décrites ci-dessus. Il n’y a pas de mesure de couverture de
lignes revendiquée ni de CI ajoutée par cette correction.

## Limites hors périmètre

La concurrence des inscriptions, l’ancien `statut_id = 1` dans leur traitement,
les badges de statut statiques et les anciennes valeurs nulles ne sont pas refondus
ici. Le profil utilisateur, l’approbation des organisateurs et la réinitialisation
du mot de passe restent des fonctionnalités distinctes.
