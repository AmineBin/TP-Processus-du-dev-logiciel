
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Création de compte</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>

<header class="header">
    <h1>Création de compte</h1>
</header>

<main class="container">

    <form method="POST" action="{{ url('/register') }}" class="card form-card">

        @csrf

        <!-- Affichage des erreurs -->
        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Pseudo -->
        <div>
            <label for="nom_utilisateur">Pseudo</label>
            <input
                type="text"
                id="nom_utilisateur"
                name="nom_utilisateur"
                value="{{ old('nom_utilisateur') }}"
                placeholder="Dio"
                required
            >
        </div>

        <!-- Nom -->
        <div>
            <label for="nom">Nom</label>
            <input
                type="text"
                id="nom"
                name="nom"
                value="{{ old('nom') }}"
                placeholder="Brando"
                required
            >
        </div>

        <!-- Prénom -->
        <div>
            <label for="prenom">Prénom</label>
            <input
                type="text"
                id="prenom"
                name="prenom"
                value="{{ old('prenom') }}"
                placeholder="Diego"
                required
            >
        </div>

        <!-- Adresse mail -->
        <div>
            <label for="email">Adresse mail</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="dio.brando@gmail.com"
                required
            >
        </div>

        <!-- Téléphone -->
        <div>
            <label for="numero_telephone">Numéro de téléphone</label>
            <input
                type="tel"
                id="numero_telephone"
                name="numero_telephone"
                value="{{ old('numero_telephone') }}"
                inputmode="tel"
                placeholder="06 12 34 56 78"
                required
            >
        </div>

        <!-- Date de naissance -->
        <div>
            <label for="date_naissance">Date de naissance</label>
            <input
                type="date"
                id="date_naissance"
                name="date_naissance"
                value="{{ old('date_naissance') }}"
                required
            >
        </div>

        <!-- Mot de passe -->
        <div>
            <label for="password">Mot de passe</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="6"
                required
            >
        </div>

        <!-- Confirmation du mot de passe -->
        <div>
            <label for="password_confirmation">
                Confirmer le mot de passe
            </label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                minlength="6"
                required
            >
        </div>

        <!-- Bouton -->
        <button type="submit">
            Créer mon compte
        </button>

        <p>
            Déjà inscrit ?
            <a href="{{ url('/login') }}">Se connecter</a>
        </p>

    </form>

</main>

</body>
</html>
