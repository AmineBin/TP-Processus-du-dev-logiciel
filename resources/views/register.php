<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>

    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

<header class="header">
    <h1>Formulaire de création de compte</h1>
</header>

<main class="container">
    <form method="post" action="/register" class="card form-card">
        <?= csrf_field() ?>

        <div>
            <label for="name">Pseudo</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div>
            <label for="name">Nom</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div>
            <label for="name">Prénom</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div>
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div>
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div>
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit">Créer mon compte</button>

        <p>
        Déjà inscrit ? <a href="/login">Se connecter</a>
    </p>
    </form>

    
</main>

</body>
</html>