
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Connexion</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>

<header class="header">
    <h1>Connexion</h1>
</header>

<main class="container">

    <form method="POST" action="{{ url('/login') }}" class="card form-card">

        @csrf

        @if (session('success'))
            <p class="success">
                {{ session('success') }}
            </p>
        @endif

        @if ($errors->any())
            <p class="error">
                {{ $errors->first() }}
            </p>
        @endif

        <div>
            <label for="email">Adresse e-mail</label>

            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
            >
        </div>

        <div>
            <label for="password">Mot de passe</label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <button type="submit">
            Se connecter
        </button>

        <p>
            Pas encore de compte ?
            <a href="{{ url('/register') }}">Créer un compte</a>
        </p>

    </form>

</main>

</body>
</html>
