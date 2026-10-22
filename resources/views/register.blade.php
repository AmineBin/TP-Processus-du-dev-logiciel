<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>

    <link rel="stylesheet" href="/css/login.css">
</head>

<body>

<header class="header">
    <h1>Création de compte</h1>
</header>

<main class="container">
    <form method="post" action="/register" class="card form-card">
        @csrf

        <div>
            <label for="name">Pseudo</label>
            <input type="text" id="name" name="name" placeholder="Dio" required>
        </div>

        <div>
            <label for="name">Nom</label>
            <input type="text" id="name" name="name" placeholder="Diego" required>
        </div>

        <div>
            <label for="name">Prénom</label>
            <input type="text" id="name" name="name" placeholder="Brando" required>
        </div>

        <div>
            <label for="email">Adresse mail</label>
            <input type="email" id="email" name="email" placeholder="dio.brando@gmail.com" required>
        </div>

        <div>
            <label for="tel">Numéro de téléphone</label>
            <input type="tel" id="tel" name="tel" inputmode="tel" pattern="0[1-9](?:[ .-]?[0-9]{2}){4}" placeholder="06 12 34 56 78" required>
        </div>

        <div>
            <label for="date">Date de naissance</label>
            <input type="date" id="date" name="date" value="2018-07-22" required>
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