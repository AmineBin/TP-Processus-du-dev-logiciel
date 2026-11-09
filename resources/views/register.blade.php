<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Création de compte</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>

<body class="register-page">

<header class="header">
    <h1>Création de compte</h1>
</header>

<main class="container">

    <form method="POST" action="{{ route('register.store') }}" class="card form-card register-form">

        @csrf

        @if (session('error'))
            <p class="error-message">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
            <div class="error-message">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Pseudo -->
        <div>
            <label for="username">Pseudo</label>
            <input type="text" id="username" name="username"
                value="{{ old('username') }}" placeholder="Dio" required>
        </div>

        <!-- Adresse mail -->
        <div>
            <label for="email">Adresse mail</label>
            <input type="email" id="email" name="email"
                value="{{ old('email') }}" placeholder="dio.brando@gmail.com" required>
        </div>

        <!-- Nom -->
        <div>
            <label for="last_name">Nom (facultatif)</label>
            <input type="text" id="last_name" name="last_name"
                value="{{ old('last_name') }}" placeholder="Brando">
        </div>

        <!-- Prénom -->
        <div>
            <label for="first_name">Prénom (facultatif)</label>
            <input type="text" id="first_name" name="first_name"
                value="{{ old('first_name') }}" placeholder="Diego">
        </div>

        <!-- Téléphone -->
        <div>
            <label for="phone">Numéro de téléphone</label>
            <input type="tel" id="phone" name="phone"
                value="{{ old('phone') }}" inputmode="tel"
                maxlength="10" placeholder="0612345678" required>
        </div>

        <!-- Date de naissance -->
        <div>
            <label for="birth_date">Date de naissance</label>
            <input type="date" id="birth_date" name="birth_date"
                value="{{ old('birth_date') }}" required>
        </div>

        <!-- Choix du rôle -->
        <div class="role-selection">
            <label>Je souhaite créer un compte en tant que :</label>

            <div class="role-options">
                <label class="role-option">
                    <input type="radio" name="role" value="UTILISATEUR"
                        {{ old('role', 'UTILISATEUR') === 'UTILISATEUR' ? 'checked' : '' }}>
                    <span>Utilisateur</span>
                    <small>Participer aux tournois</small>
                </label>

                <label class="role-option">
                    <input type="radio" name="role" value="ORGANISATEUR"
                        {{ old('role') === 'ORGANISATEUR' ? 'checked' : '' }}>
                    <span>Organisateur</span>
                    <small>Créer et gérer des tournois</small>
                </label>
            </div>
        </div>

        <!-- Mot de passe -->
        <div>
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password"
                minlength="8" required>
        </div>

        <!-- Confirmation -->
        <div>
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input type="password" id="password_confirmation"
                name="password_confirmation" minlength="8" required>
        </div>

        <button type="submit">Créer mon compte</button>

        <p>
            Déjà inscrit ?
            <a href="{{ route('login') }}">Se connecter</a>
        </p>

    </form>

</main>

</body>
</html>
