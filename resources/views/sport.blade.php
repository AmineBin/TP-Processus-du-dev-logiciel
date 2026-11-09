
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

    {{-- Récupérer les tournois auxquels l'utilisateur est inscrit --}}

    @php
        $mesInscriptions = $tournois->filter(function ($tournoi) {
            return !is_null($tournoi->inscription_tournoi_id);
        });
    @endphp

    {{-- RÉCAPITULATIF DES INSCRIPTIONS --}}

    <section class="registration-summary">

        <h2>Mes inscriptions dans cette catégorie</h2>

        @if ($mesInscriptions->isNotEmpty())

            <p class="registration-intro">
                Tu es bien inscrite à {{ $mesInscriptions->count() }}
                tournoi(s) dans cette catégorie.
            </p>

            <div class="registration-list">

                @foreach ($mesInscriptions as $tournoi)

                    <article class="registration-item">

                        <h3>{{ $tournoi->nom }}</h3>

                        <p>
                            <strong>Date de début :</strong>
                            {{ \Carbon\Carbon::parse($tournoi->date_debut)->format('d/m/Y à H:i') }}
                        </p>

                        <p>
                            <strong>Date de fin :</strong>
                            {{ \Carbon\Carbon::parse($tournoi->date_fin)->format('d/m/Y à H:i') }}
                        </p>

                        <span class="registration-confirmed">
                            ✓ Inscription confirmée
                        </span>

                    </article>

                @endforeach

            </div>

        @else

            <p class="registration-empty">
                Tu n'es inscrite à aucun tournoi dans cette catégorie pour le moment.
            </p>

        @endif

    </section>

    {{-- LISTE DES TOURNOIS --}}

    <h2>Tournois disponibles</h2>

    @forelse ($tournois as $tournoi)

        <article class="tournament-card">

            <div class="tournament-banner">
                {{ strtoupper($tournoi->nom) }}
            </div>

            <div class="tournament-content">

                {{-- Afficher la situation de l'utilisateur --}}

                @if ($tournoi->inscription_tournoi_id !== null)

                    <span class="status">
                        Tu es inscrite
                    </span>

                @else

                    <span class="status-pending">
                        Tournoi disponible
                    </span>

                @endif

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

                {{-- Ne pas proposer une deuxième inscription --}}

                @if ($tournoi->inscription_tournoi_id === null)

                    <a
                        href="{{ url('/tournoi/' . $tournoi->id . '/inscription') }}"
                        class="btn-register"
                    >
                        S'inscrire au tournoi
                    </a>

                @else

                    <p class="already-registered">
                        ✓ Ton inscription à ce tournoi est déjà enregistrée.
                    </p>

                @endif

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
