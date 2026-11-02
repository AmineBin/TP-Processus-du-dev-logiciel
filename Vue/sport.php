
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} - Tournois</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <header class="header">
        <h1>{{ $name }}</h1>
    </header>

    <main class="container">
        <a class="back" href="/home">← Retour</a>

        @forelse ($categories as $categorie)
            <div class="category">
                <h2>{{ $categorie->nom }}</h2>
                <a href="{{ url('/categorie/' . $categorie->id) }}">
                    Voir les tournois
                </a>
            </div>
        @empty
            <p class="empty">Aucune sous-catégorie disponible.</p>
        @endforelse

    </main>
</body>
</html>
