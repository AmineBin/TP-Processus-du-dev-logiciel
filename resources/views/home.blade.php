
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tournois</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <style>
        /* Position du logo profil */
        .profile-link {
            position: absolute;
            top: 20px;
            right: 25px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            border: 2px solid #8b5cf6;
            border-radius: 50%;
            color: #8b5cf6;
            text-decoration: none;
            transition: 0.3s ease;
            z-index: 10;
        }

        .profile-link:hover {
            background-color: #8b5cf6;
            color: #ffffff;
            transform: scale(1.08);
        }

        .profile-link svg {
            width: 27px;
            height: 27px;
        }

        .header {
            position: relative;
        }

        /* Adaptation pour les téléphones */
        @media screen and (max-width: 600px) {
            .profile-link {
                top: 12px;
                right: 12px;
                width: 42px;
                height: 42px;
            }
        }
    </style>
</head>

<body>

<header class="header">

    <!-- Logo profil cliquable -->
    <a href="{{ url('/profil') }}"
       class="profile-link"
       title="Mon profil"
       aria-label="Accéder à mon profil">

        <svg xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round">

            <circle cx="12" cy="8" r="4"></circle>
            <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>

        </svg>
    </a>

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
