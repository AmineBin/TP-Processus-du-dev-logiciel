# Tournager — Plateforme d’organisation de tournois

Projet réalisé dans le cadre du TP de processus du développement logiciel, avec la méthodologie **Scrum**. Tournager permet de consulter et d’organiser des tournois de sports, de jeux vidéo et de jeux de table.

## Besoins du client — groupe 2

Les besoins suivants constituent la base du **Product Backlog** ; leur priorité et leurs critères d’acceptation doivent être validés avec le Product Owner (PO). Ils ne décrivent pas des fonctionnalités déjà implémentées.

- Consulter des tournois par catégorie, discipline et lieu, éventuellement à proximité.
- Afficher une fiche tournoi : nom, image, description, organisateur, date, lieu, format des équipes, nombre de participants, conditions d’éligibilité et informations pratiques.
- Gérer l’inscription et la connexion des utilisateurs, ainsi que les rôles d’administrateur/organisateur et de joueur, dont les droits restent à préciser.
- Créer des tournois et définir leurs règles, restrictions d’âge ou de niveau (Elo), frais d’inscription et récompenses.
- Inscrire des joueurs aux tournois et consulter la liste des participants.
- Gérer les scores et classements selon la discipline.
- Recevoir un e-mail lorsqu’un tournoi pertinent se déroule à proximité.
- Signaler un compte en cas d’infraction, par exemple une fausse déclaration d’âge.

**Exemples de disciplines :** basket, football, tennis, tennis de table, athlétisme ; jeux vidéo de combat, sport, quiz, FPS et speedrun ; échecs et poker. Les catégories et restrictions applicables sont à confirmer avec le PO.

**Pistes à clarifier :** notifications SMS, équilibrage par Elo, onboarding par centres d’intérêt, vérification des organisateurs, contrôle parental, réservation des lieux et gestion du personnel. La gestion des frais ne signifie pas nécessairement un paiement en ligne : ce périmètre reste à valider.

## Organisation Scrum

Le suivi se fait dans **Trello**. Linear a été évoqué dans les notes initiales, mais n’est pas retenu ici comme second outil de suivi.

- **Product Backlog :** liste ordonnée des besoins du produit, maintenue avec le PO.
- **Sprint Backlog :** user stories sélectionnées pour le sprint, avec tâches techniques, responsables et estimation de charge.
- **Planification :** convenir d’un objectif de sprint et répartir le travail selon les dépendances et disponibilités.
- **Mêlées courtes :** partager l’avancement, les prochaines actions et les blocages.
- **Revue :** démontrer l’incrément au PO et recueillir ses retours.
- **Rétrospective :** identifier les améliorations de fonctionnement de l’équipe.
- **Compte rendu de sprint :** objectif, travail prévu/réalisé, état des backlogs, retours de revue et actions issues de la rétrospective.

### Premier incrément envisagé

**Objectif : permettre au visiteur de découvrir les tournois proposés.**

La séance 1 prévoit une liste d’événements avec des données en dur : discipline, lieu, date, public concerné et conditions d’éligibilité. La page d’accueil et la préparation de la base de données soutiennent cet objectif ; une base de données seule ne constitue pas une valeur visible pour le client.

L’authentification peut être planifiée ensuite, ou dans le sprint courant si elle sert son objectif, après validation du PO et coordination sur le modèle utilisateur.

### Exemple de user story

> En tant que visiteur, je veux consulter les tournois disponibles afin de choisir un événement adapté à mes préférences.

**Critère d’acceptation :** étant donné que des tournois sont disponibles, quand j’ouvre la liste des événements, alors je vois leur discipline, leur lieu, leur date et leurs conditions d’éligibilité.

### Definition of Done — proposition à valider avec le PO

Une user story est terminée lorsque :

- ses critères d’acceptation sont satisfaits ;
- le code est relu, les tests pertinents sont réalisés et passent ;
- les changements de modèle respectent le diagramme de classes validé, mis à jour si nécessaire ;
- les migrations nécessaires et la documentation sont versionnées, sans secrets ;
- la Pull Request est fusionnée dans `develop` et la fonctionnalité est démontrable ;
- la carte Trello est mise à jour avec le résultat de la validation.

## Collaboration et modèle de données

Le schéma doit être cohérent avec le diagramme de classes et les besoins retenus : utilisateurs et rôles, catégories et sous-catégories, tournois/événements, restrictions et informations complémentaires. Les relations et contraintes restent à valider ; cette liste n’est pas un schéma définitif.

Coordonner les migrations entre les personnes chargées de la base de données et des fonctionnalités, notamment l’authentification, pour éviter les doublons.

Le workflow utilise `main`, `develop` et une branche `feature/<nom>` par fonctionnalité. Voir [le guide de contribution](CONTRIBUTING.md). Aucune CI n’est prévue pour le moment.

## Environnement technique

Application Laravel 13 exécutée en local. Docker sert uniquement à lancer MySQL 8.4 et Adminer.

### Prérequis

- PHP 8.3 ou plus récent, avec l'extension `pdo_mysql`
- Composer
- Docker avec Docker Compose
- Node.js et npm uniquement si vous modifiez les ressources front-end

## Installation

Depuis la racine du projet :

```bash
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d
php artisan migrate
```

Attendez que MySQL soit prêt avant de lancer les migrations (`docker compose ps` doit indiquer `healthy`). Si la connexion échoue au premier essai, patientez quelques secondes et réessayez.

## Lancement

```bash
docker compose up -d
php artisan serve
```

L'application est accessible sur http://localhost:8000. Exécutez les commandes Laravel directement sur votre machine, par exemple `php artisan migrate` ou `php artisan test`. MySQL écoute uniquement en local sur le port `3307`.

Adminer est accessible sur http://localhost:8080. Sur la page de connexion, choisissez **MySQL**, puis utilisez : serveur `mysql` (nom du service Docker, et non `127.0.0.1`), utilisateur `laravel`, mot de passe `password`, base `laravel`. Ces identifiants correspondent aux valeurs par défaut de `.env.example` ; utilisez ceux de votre `.env` si vous les avez modifiés. Adminer n'est accessible que depuis votre machine.

Le fichier `.env` est privé et ignoré par Git. Les identifiants de `.env.example` sont destinés uniquement au développement local. Si vous modifiez `DB_PORT`, ajustez également `FORWARD_DB_PORT` pour qu'ils restent identiques. Si le port `8080` est occupé, modifiez `ADMINER_PORT` dans `.env`. Les identifiants MySQL ne sont appliqués qu'à la création du volume : pour repartir de zéro, utilisez `docker compose down -v` (cette commande efface les données).


Arrêt de la base de données (les données sont conservées) : `docker compose down`.
