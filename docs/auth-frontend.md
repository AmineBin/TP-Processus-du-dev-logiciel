# Contrat frontend de l’authentification

## Routes

Toutes ces routes utilisent les cookies de session et les protections du groupe
`web`. `active` est exécuté avant `auth` ou `guest`.

| Méthode | URL | Nom Laravel | Accès | Réponse HTML normale |
| --- | --- | --- | --- | --- |
| GET | `/` | `index` | Public | Redirection vers `/login` ou `/home`. |
| GET | `/login` | `login` | Visiteur | Formulaire de connexion. |
| POST | `/login` | `login.store` | Visiteur | Redirection vers la page GET demandée, sinon `/home`. |
| GET | `/register` | `register` | Visiteur | Formulaire de création de compte. |
| POST | `/register` | `register.store` | Visiteur | Redirection vers `/login` avec confirmation. |
| POST | `/logout` | `logout` | Connecté | Redirection vers `/login` avec confirmation. |

Un utilisateur connecté accédant aux routes `guest` est redirigé vers `/home`.
Un visiteur accédant aux routes protégées est redirigé vers `/login`.

## Données des formulaires

Pour `/login`, envoyer `email` (adresse valide, maximum 100 caractères) et
`password` (chaîne non vide).

Pour `/register` :

| Champ envoyé | Règle serveur | Colonne correspondante |
| --- | --- | --- |
| `username` | Obligatoire, unique, maximum 50 caractères. | `nom_utilisateur` |
| `last_name` | Facultatif, maximum 50 caractères. | `nom` |
| `first_name` | Facultatif, maximum 50 caractères. | `prenom` |
| `email` | Obligatoire, adresse valide, unique, maximum 100 caractères. | `email` |
| `phone` | Obligatoire, chaîne, maximum 10 caractères (ex. `0612345678`). | `numero_telephone` |
| `birth_date` | Obligatoire, date strictement antérieure à aujourd’hui. | `date_naissance` |
| `password` | Obligatoire, chaîne d’au moins 8 caractères. | `mot_de_passe_hash`, après hachage. |
| `password_confirmation` | Doit correspondre à `password`. | Non enregistrée. |

Le frontend ne choisit jamais `role_id` ou `est_actif`. Ces valeurs supplémentaires
ne permettent pas de créer un compte administrateur ou de modifier l’état initial.
Le serveur reste l’autorité de validation, même si le HTML ajoute ses contraintes.

## Blade : parcours actuel

Les formulaires utilisent les noms de routes, `method="POST"` et `@csrf`.
Après un échec, le backend redirige vers le formulaire concerné :

- `$errors` contient les erreurs par champ ou les identifiants incorrects ;
- `session('error')` contient une erreur globale, par exemple un rôle indisponible ;
- `session('error_code')` donne le code applicatif stable ;
- `old('email')`, `old('username')`, etc. restaurent uniquement les champs autorisés ;
- les mots de passe et leur confirmation ne sont jamais placés dans l’ancienne saisie ;
- `session('success')` contient une confirmation après création ou déconnexion.

Les vues affichent les messages avec `{{ ... }}`, donc avec échappement HTML.
Ne pas remplacer cela par du HTML non échappé.

## Client JavaScript : réponses JSON

Envoyer `Accept: application/json` aux mêmes routes pour recevoir les réponses JSON.
Cela ne transforme pas ces routes en API sans état : il faut conserver le cookie de
session et transmettre un jeton CSRF valide lors des POST. Une application sur une
autre origine nécessiterait une configuration spécifique, non implémentée ici.

Succès :

| Action | Statut | Corps |
| --- | --- | --- |
| Connexion | 200 | `{"message":"Connexion réussie."}` |
| Création du compte | 201 | `{"message":"Compte créé avec succès."}` |
| Déconnexion | 200 | `{"message":"Tu as été déconnecté."}` |

Erreur de validation, par exemple :

```json
{
  "code": "VALIDATION_FAILED",
  "message": "Certains champs sont invalides.",
  "errors": {
    "email": ["L’adresse e-mail doit être valide."]
  }
}
```

Utiliser `code` pour les décisions du frontend, `message` pour le message global et
`errors` pour l’affichage sous les champs. Ne pas déduire le type d’erreur à partir
du texte traduit. Les succès JSON ne redirigent pas automatiquement le navigateur.

Après déconnexion, ou révocation d’un compte désactivé, le jeton CSRF précédent
n’est plus valide : recharger le formulaire avant un nouveau POST.

Voir [les codes et statuts](errors.md) et [l’architecture backend](authentication.md).
