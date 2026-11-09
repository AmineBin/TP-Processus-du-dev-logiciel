# Architecture de l’authentification

## Périmètre

L’application utilise Laravel 13 et le guard `web` : l’authentification repose sur
une session et un cookie, pas sur un JWT. Les mêmes actions peuvent répondre en
JSON lorsque le client envoie `Accept: application/json`.

`routes/web.php` contient uniquement des déclarations de routes, des noms et des
middlewares. Il n’exécute aucune requête SQL, validation ou règle métier.
Il n’existe pas de `routes/api.php` dans cette version.

Pour une lecture visuelle, voir les [schémas Mermaid des fichiers et des parcours](auth-diagrams.md).

## Responsabilités

| Fichier ou dossier | Responsabilité |
| --- | --- |
| `routes/web.php` | Associer une URL à une action et déclarer les contrôles d’accès. |
| `app/Http/Requests/LoginRequest.php` | Valider l’adresse e-mail et le mot de passe de connexion. |
| `app/Http/Requests/RegisterRequest.php` | Valider les données de création de compte et leur unicité. |
| `app/Http/Requests/ValidatedRequest.php` | Partager le contrat HTML/JSON de validation et la liste blanche d’anciennes saisies. |
| `app/Http/Requests/AuthRequest.php` | Ajouter les messages et anciennes saisies propres à l’authentification. |
| `app/Http/Controllers/UserController.php` | Afficher les formulaires, connecter, créer le compte et déconnecter. |
| `app/Http/Middleware/Authenticate.php` | Refuser les visiteurs sur les routes protégées. |
| `app/Http/Middleware/RedirectIfAuthenticated.php` | Réserver les formulaires aux visiteurs. |
| `app/Http/Middleware/EnsureActiveAccount.php` | Révoquer la session d’un compte désactivé. |
| `app/Models/User.php` | Adapter l’utilisateur authentifiable au schéma `utilisateur`. |
| `app/Models/Role.php` | Représenter les rôles et leurs codes métier. |
| `app/Support/ErrorHandler.php` | Traduire les codes d’erreur et produire les réponses publiques. |
| `bootstrap/app.php` | Enregistrer les alias des middlewares et le rendu des erreurs de limitation. |

Les traitements déjà présents de catégories et de tournois ont été déplacés vers
`HomeController` et `TournamentController`. Ils ne doivent pas rejoindre
`UserController`. Le profil et la gestion des inscriptions sont maintenant décrits
[dans leur contrat dédié](profile-and-registrations.md) : slugs de catégories,
statuts résolus par code, participations annulées et protection des places.

## Ordre des traitements

1. Laravel démarre la session et vérifie le CSRF des requêtes modifiant l’état.
2. `active` vérifie le compte éventuellement connecté et révoque une session désactivée.
3. `guest` réserve les formulaires aux visiteurs ; `auth` protège les autres pages.
4. Pour `POST /login`, `throttle:6,1` limite les tentatives à six requêtes par minute et par IP.
5. La `FormRequest` valide les champs avant d’appeler le contrôleur.
6. Le contrôleur utilise les modèles et les mécanismes Laravel, puis retourne une réponse.

Les alias `auth` et `guest` sont remplacés explicitement par les classes du projet.
Ils concernent uniquement le guard de session par défaut ; ils ne constituent pas
une implémentation multi-guard ou `auth:sanctum`.

### Connexion

`Auth::attempt()` accepte uniquement un compte actif. Le modèle indique que le
hachage se trouve dans `mot_de_passe_hash`, et non `password`. Une adresse absente,
un mauvais mot de passe et un compte désactivé produisent le même message afin de
ne pas révéler l’état d’un compte.

La session est régénérée après connexion. Une page GET protégée demandée auparavant
est reprise ; sinon le navigateur revient sur `/home`. Un POST n’est jamais rejoué.

### Création de compte

`RegisterRequest` vérifie les champs et les contraintes d’unicité. Le contrôleur :

- recherche le rôle dont le code est `UTILISATEUR` ;
- hache le mot de passe avec `Hash::make()` ;
- associe le rôle côté serveur puis enregistre le modèle ;
- ne connecte pas automatiquement le nouveau compte.

`role_id` et `est_actif` ne sont ni validés comme entrées ni autorisés dans
l’assignation de masse de `User`. La base applique `est_actif = true` par défaut.
Le hachage est masqué lors de la sérialisation du modèle.

La contrainte SQL reste nécessaire : elle protège les inscriptions simultanées,
même après une validation d’unicité réussie. Un conflit concurrent produit
`AUTH_ACCOUNT_CONFLICT`. Une autre erreur SQL pendant la création est journalisée
et remplacée par `AUTH_REGISTRATION_FAILED` dans la réponse.

### Déconnexion et compte désactivé

La déconnexion est un POST protégé. Elle appelle `Auth::logout()`, invalide toute
la session et renouvelle le jeton CSRF. Le middleware `active` effectue également
ces opérations si le compte a été désactivé entre deux requêtes.
Le schéma n’ayant pas de `remember_token`, la connexion persistante n’est pas proposée.

## Corriger l’erreur de clé étrangère `fk_utilisateur_role`

L’ancien code imposait `role_id = 1`. Or ce rôle peut être absent, ou son ID peut
être différent dans une autre base. Ne pas désactiver la contrainte de clé étrangère.

Après configuration de `.env`, démarrage de MySQL et application des migrations :

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

`RoleSeeder` ajoute ou actualise `UTILISATEUR`, `ADMIN` et `ORGANISATEUR` par code, conserve les IDs
existants et ne modifie aucun compte. Il peut être exécuté plusieurs fois. Le
contrôleur utilise ensuite l’ID réellement trouvé, qu’il vaille `1`, `42` ou autre.

Si `UTILISATEUR` manque encore, l’inscription est refusée proprement avec
`AUTH_ROLE_UNAVAILABLE` : redirection vers le formulaire, ou HTTP 503 en JSON.
Elle ne tente pas d’insérer un utilisateur avec un rôle inexistant.

Ne pas utiliser `migrate:fresh` pour cette réparation : cela supprimerait les données.
Les seeders de démonstration restent réservés au développement. Le `DatabaseSeeder`
crée désormais ses comptes uniquement s’ils manquent, sans réinitialiser les mots de
passe, rôles ou états actifs existants. Pour un frontend entièrement peuplé, voir
[le jeu de démonstration complet](demo-data.md).

## Vérifications et couverture documentaire

```bash
php artisan test
php artisan route:list -v --except-vendor
```

Les tests d’authentification utilisent exclusivement SQLite en mémoire et ne
modifient pas la base MySQL configurée. Les classes et méthodes publiques ajoutées
pour ce chantier comportent des commentaires PHPDoc ; les signatures précisent les
types. Aucune mesure de couverture de lignes n’est revendiquée.

| Zone documentée | Vérifications automatisées |
| --- | --- |
| Routes et middlewares | Accès visiteur, accès connecté, compte désactivé et redirections. |
| Requests | Validation, messages français et absence de mots de passe en ancienne saisie. |
| Contrôleur et session | Connexion réelle, rotation de session, déconnexion et réponses JSON. |
| Utilisateur et rôles | ID standard différent de 1, rôle absent, injection de rôle ignorée, seeder idempotent. |
| ErrorHandler | Catalogue, code inconnu, erreurs JSON, limitation et absence de détails SQL dans les réponses testées. |
| Compatibilité des écrans | Accueil, catégories et inscription à un tournoi sans doublon. |

Voir aussi [le contrat frontend](auth-frontend.md), [le catalogue des erreurs](errors.md)
et [la création de tournois et ses autorisations](tournament-creation.md).
Références Laravel : [authentification](https://laravel.com/docs/13.x/authentication),
[Form Requests](https://laravel.com/docs/13.x/validation#form-request-validation).
