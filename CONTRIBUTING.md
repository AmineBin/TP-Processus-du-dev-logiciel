# Guide des branches

- `main` : version stable du projet.
- `develop` : intégration des fonctionnalités en cours.
- `feature/<nom>` : une branche par fonctionnalité, créée depuis `develop`.

## Développer une fonctionnalité

Partir d'une branche `develop` à jour :

```bash
git switch develop
git pull --ff-only origin develop
git switch -c feature/nom-de-la-fonctionnalite
```

Après les modifications, enregistrer et publier le travail :

```bash
git add chemin/du/fichier
git commit -m "Ajout de la fonctionnalité"
git push -u origin feature/nom-de-la-fonctionnalite
```

Ouvrir une Pull Request de `feature/nom-de-la-fonctionnalite` vers `develop`. Faire relire et tester les modifications avant la fusion.

Lorsque la version est validée, ouvrir une Pull Request de `develop` vers `main`. Éviter les modifications directes sur `main`.

## Branches existantes

Conserver les branches existantes, notamment `laeticia`, jusqu'à la vérification et l'intégration de leur travail. Pour intégrer le frontend, ouvrir une Pull Request de `laeticia` vers `develop` et résoudre les éventuels conflits avant la fusion.

Aucune CI n'est mise en place pour le moment.
