
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

    <h1>Tournois de {{ $name }}</h1>

    <p>Découvre les tournois disponibles et inscris ton équipe !</p>

</header>


<main class="container">

    <a class="back" href="{{ url('/home') }}">
        Retour à l'accueil
    </a>


    {{-- Messages de confirmation et d'erreur --}}

    @if (session('success'))
        <p class="success">
            {{ session('success') }}
        </p>
    @endif

    @if (session('error'))
        <p class="error">
            {{ session('error') }}
        </p>
    @endif


    <h2>Tournois disponibles</h2>


    @forelse ($tournois as $tournoi)

        <article class="tournament-card">

            <div class="tournament-banner">

                {{ strtoupper($tournoi->nom) }}

            </div>


            <div class="tournament-content">

                <span class="status">
                    Tournoi disponible
                </span>


                <h3>{{ $tournoi->nom }}</h3>


                <p class="description">
                    {{ $tournoi->description }}
                </p>


                <h4>Informations du tournoi</h4>


                <ul class="tournament-info">

                    <li>
                        <strong>Date de début :</strong>

                        {{ \Carbon\Carbon::parse($tournoi->date_debut)->format('d/m/Y à H:i') }}
                    </li>


                    <li>
                        <strong>Date de fin :</strong>

                        {{ \Carbon\Carbon::parse($tournoi->date_fin)->format('d/m/Y à H:i') }}
                    </li>


                    <li>
                        <strong>Date limite d'inscription :</strong>

                        {{ \Carbon\Carbon::parse($tournoi->date_limite_inscription)->format('d/m/Y à H:i') }}
                    </li>


                    <li>
                        <strong>Nombre maximum de participants :</strong>

                        {{ $tournoi->nombre_max_participants }}
                    </li>

                </ul>


                <div class="eligibility">

                    <h4>Règlement du tournoi</h4>

                    <p>
                        {{ $tournoi->regles }}
                    </p>

                </div>


                {{-- Bouton vers la page de confirmation --}}

                <a
                    href="{{ url('/tournoi/' . $tournoi->id . '/inscription') }}"
                    class="btn-register"
                >
                    S'inscrire au tournoi
                </a>

            </div>

        </article>


    @empty

        <p class="empty">

            Aucun tournoi n'est disponible pour cette catégorie
            pour le moment. Reviens bientôt pour découvrir
            les prochains tournois.

        </p>

    @endforelse

</main>


<footer class="footer">

    <p>© 2026 - Sport &amp; Tournois</p>

</footer>

</body>
</html>
