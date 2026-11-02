
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tournois</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

<header class="header">
    <h1>Tournois</h1>
    <p>Choisissez une catégorie</p>
</header>

<main class="container">

    @foreach ($categories as $categorie)

        <section class="section">

            <h2>{{ $categorie->nom }}</h2>

            <p>{{ $categorie->description }}</p>

            <ul class="grid">

                @forelse ($categorie->sous_categories as $sousCategorie)

                    <li class="card">
                        <a href="{{ route('sport', ['name' => \Illuminate\Support\Str::slug($sousCategorie->nom)]) }}">
                            {{ $sousCategorie->nom }}
                        </a>
                    </li>

                @empty

                    <li class="card">
                        Aucune sous-catégorie disponible.
                    </li>

                @endforelse

            </ul>

        </section>

    @endforeach

</main>

</body>
</html>
