# TP Processus du développement logiciel

Application Laravel 13 exécutée en local. Docker sert uniquement à lancer MySQL 8.4 et Adminer.

## Prérequis

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

Pour les ressources front-end, si nécessaire :

```bash
npm install
npm run dev
```

Arrêt de la base de données (les données sont conservées) : `docker compose down`.
