# Jeu de démonstration pour le frontend

## Charger les données

Après configuration de `.env`, démarrage de MySQL et application des migrations :

```bash
php artisan db:seed --class=DemoSeeder
php artisan serve
```

`DemoSeeder` refuse de s’exécuter hors des environnements `local` et `testing`.
Ne pas changer artificiellement l’environnement d’une base de production pour
contourner cette protection. Ces comptes ont des mots de passe publics et sont
réservés au développement.

Le seeder complète les **sept tables métier**. Il ne remplit pas les tables
techniques Laravel de sessions, cache, jobs ou réinitialisation de mot de passe,
ni l’ancienne table `users` : l’application utilise `utilisateur`.

## Compte pour parcourir les écrans

Sur <http://localhost:8000/login> :

- **Adresse :** `demo.visiteur@example.test`
- **Mot de passe :** `DemoTournois2026!`

Huit autres participants sont disponibles : `demo.joueur01@example.test` à
`demo.joueur08@example.test`, avec le même mot de passe initial.

Le jeu minimal conserve également `test@example.com` et `admin@example.com`, avec
`password` comme mot de passe **uniquement lors de leur création**. Aucun mot de
passe, rôle, téléphone ou état actif d’un compte existant n’est réinitialisé.
Si un compte de démonstration existait déjà et a été personnalisé, ses données
et ses identifiants actuels restent valables.

## Contenu sur une base vide

| Données métier | Quantité |
| --- | --- |
| Rôles | 2 |
| Statuts d’inscription | 3 |
| Statuts de tournoi | 3 |
| Catégories racines | 3 |
| Disciplines | 10 |
| Utilisateurs | 11 : 2 comptes du jeu minimal et 9 comptes supplémentaires |
| Tournois | 15 |
| Inscriptions | 20 |

Les totaux réels peuvent être supérieurs si la base contenait déjà des données.

### Catégories et pages

| Racine | Discipline | Page | Tournois du seed |
| --- | --- | --- | --- |
| Jeux vidéo | FPS | `/sport/fps` | 3 |
| Jeux vidéo | Combat | `/sport/combat` | 2 |
| Jeux vidéo | Speedrun | `/sport/speedrun` | 1 |
| Sports | Football | `/sport/football` | 2 |
| Sports | Basketball | `/sport/basketball` | 1 |
| Sports | Tennis | `/sport/tennis` | 1 |
| Sports | Natation | `/sport/natation` | 1 |
| Jeux de table | Échecs | `/sport/echecs` | 2 |
| Jeux de table | Poker | `/sport/poker` | 2 |
| Jeux de table | Jeux de société | `/sport/jeux-de-societe` | 0, pour tester le message de liste vide |

## Scénarios à essayer

Se connecter avec le compte de visite, ouvrir `/home`, choisir une discipline,
puis utiliser le lien d’inscription du tournoi concerné. Les IDs sont attribués
par la base : ne pas supposer qu’un tournoi a un ID fixe.

| Tournoi | Scénario attendu |
| --- | --- |
| Tournoi FPS de démonstration | Inscription ouverte : 2 participants pour 32 places. |
| Démo — Nuit Valorant | Autre tournoi ouvert, avec 3 participants pour 16 places. |
| Démo — Football découverte complet | 8 participants pour 8 places : confirmer l’inscription affiche le refus pour capacité atteinte. |
| Démo — Open tennis inscriptions closes | Tournoi futur, mais date limite dépassée : confirmer affiche le refus pour clôture. |
| Démo — Open blitz échecs | Le compte de visite est déjà inscrit : la fiche affiche le message correspondant. |
| Démo — Masters échecs | Tournoi dans deux jours, clôture demain. |
| Démo — Arena Counter-Strike | Tournoi en cours, avec clôture passée. |
| Démo — Finale poker terminée | Tournoi passé, terminé la semaine précédente. |
| Démo — Coupe rétro terminée | Autre exemple de tournoi terminé. |

Les autres tournois servent à varier les textes, les règlements, les dates et les
capacités visibles sur les cartes. Le poker utilise uniquement des jetons fictifs,
sans argent réel.

**Limite du frontend actuel :** `sport.blade.php` affiche encore le badge fixe
« Tournoi disponible » et le lien d’inscription, indépendamment du statut SQL.
Le seed ne change pas cette présentation. Les dates et descriptions permettent de
repérer les scénarios ; les refus de capacité ou de clôture se voient après
confirmation. La gestion des statuts dans les badges reste un chantier distinct.

## Réexécution et conservation des données

- Aucune table n’est vidée ; aucun utilisateur ou tournoi n’est supprimé.
- Le jeu minimal `DatabaseSeeder` est chargé avant les scénarios supplémentaires.
- Les rôles et statuts sont retrouvés par code, sans imposer leurs IDs.
- Les catégories sont réutilisées ; les nouvelles disciplines sont rattachées à leur racine.
- Les comptes sont créés uniquement si leur adresse manque. Si un autre compte
  possède déjà le pseudo prévu, le nouveau compte reçoit un suffixe `_demo_N`.
- Les tournois préfixés `Démo —` et le tournoi FPS minimal sont actualisés par nom :
  leurs dates sont recalculées relativement au moment du seed. Réserver ces noms
  aux fixtures, car leurs données seront remplacées lors d’une réexécution.
- Les inscriptions existantes ne sont pas modifiées ; seules les paires
  utilisateur/tournoi manquantes du jeu de démonstration sont ajoutées.
- Rôles, statuts et certaines descriptions de catégories du jeu minimal peuvent
  être actualisés. Ce n’est donc pas une commande sans écriture sur les données existantes.
- L’ensemble s’exécute dans une transaction : une erreur annule les écritures de cette exécution.

Ne pas utiliser `migrate:fresh` ou désactiver les clés étrangères pour ce seed.
Le seeder est stable en nombre de lignes sur deux exécutions successives, mais il
ne remet pas à zéro les actions faites ensuite dans l’interface. Une inscription
réalisée manuellement est conservée et peut modifier les scénarios.

## Vérifier les fixtures

```bash
php artisan test --filter=DemoSeederTest
```

Les tests s’exécutent dans SQLite en mémoire et couvrent les volumes initiaux,
la réexécution, la conservation des comptes et d’un tournoi personnel, les IDs de
référence différents de 1, les pages du frontend, les refus d’inscription et la
protection contre une exécution en production.
