
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $name ?? 'Tous les sports' }} - Tournois</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

    <!-- EN-TÊTE -->
    <header class="header">
        <h1>Tournois de {{ $name ?? 'Tous les sports' }}</h1>
        <p>Découvre les tournois disponibles et inscris-toi !</p>
    </header>

    <main class="container">

        <!-- RETOUR À L'ACCUEIL -->
        <a class="back" href="{{ route('home') }}">
            ← Retour à l'accueil
        </a>

        <!-- MESSAGES -->
        @if (session('success'))
            <p class="success">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p class="error">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- RÉCAPITULATIF DES INSCRIPTIONS -->
        @php
            $mesInscriptions = $tournois->filter(function ($tournoi) {
                return !empty($tournoi->inscription_tournoi_id);
            });
        @endphp

        <section class="registration-summary">

            <h2>Mes inscriptions dans cette catégorie</h2>

            @if ($mesInscriptions->isNotEmpty())

                <p class="registration-intro">
                    Tu es inscrit(e) à {{ $mesInscriptions->count() }}
                    tournoi(s) dans cette catégorie.
                </p>

                <div class="registration-list">

                    @foreach ($mesInscriptions as $tournoi)

                        <article class="registration-item">

                            <h3>{{ $tournoi->nom }}</h3>

                            <p>
                                <strong>Date de début :</strong>
                                {{ $tournoi->date_debut
                                    ? \Carbon\Carbon::parse($tournoi->date_debut)->format('d/m/Y à H:i')
                                    : 'Non définie' }}
                            </p>

                            <p>
                                <strong>Date de fin :</strong>
                                {{ $tournoi->date_fin
                                    ? \Carbon\Carbon::parse($tournoi->date_fin)->format('d/m/Y à H:i')
                                    : 'Non définie' }}
                            </p>

                            <span class="registration-confirmed">
                                ✓ Inscription enregistrée
                            </span>

                        </article>

                    @endforeach

                </div>

            @else

                <p class="registration-empty">
                    Tu n'es inscrit(e) à aucun tournoi dans cette catégorie
                    pour le moment.
                </p>

            @endif

        </section>

        <!-- LISTE DES TOURNOIS -->
        <h2>Tournois disponibles</h2>

        @forelse ($tournois as $tournoi)

            @php
                $estInscrit = !empty($tournoi->inscription_tournoi_id);
            @endphp

            <article class="tournament-card">

                <!-- BANNIÈRE -->
                <div class="tournament-banner">
                    {{ strtoupper($tournoi->nom) }}
                </div>

                <div class="tournament-content">

                    <!-- SITUATION DE L'UTILISATEUR -->
                    @if ($estInscrit)

                        <span class="status">
                            ✓ Tu es inscrit(e)
                        </span>

                    @else

                        <span class="status-pending">
                            Tournoi disponible
                        </span>

                    @endif

                    <!-- INFORMATIONS GÉNÉRALES -->
                    <h3>{{ $tournoi->nom }}</h3>

                    <p class="description">
                        {{ $tournoi->description ?? 'Aucune description disponible.' }}
                    </p>

                    <!-- INFORMATIONS DU TOURNOI -->
                    <h4>Informations du tournoi</h4>

                    <ul class="tournament-info">

                        <li>
                            <strong>Date de début :</strong>
                            {{ $tournoi->date_debut
                                ? \Carbon\Carbon::parse($tournoi->date_debut)->format('d/m/Y à H:i')
                                : 'Non définie' }}
                        </li>

                        <li>
                            <strong>Date de fin :</strong>
                            {{ $tournoi->date_fin
                                ? \Carbon\Carbon::parse($tournoi->date_fin)->format('d/m/Y à H:i')
                                : 'Non définie' }}
                        </li>

                        <li>
                            <strong>Date limite d'inscription :</strong>
                            {{ $tournoi->date_limite_inscription
                                ? \Carbon\Carbon::parse($tournoi->date_limite_inscription)->format('d/m/Y à H:i')
                                : 'Non définie' }}
                        </li>

                        <li>
                            <strong>Nombre maximum de participants :</strong>
                            {{ $tournoi->nombre_max_participants ?? 'Non limité' }}
                        </li>

                    </ul>

                    <!-- RÈGLEMENT -->
                    <div class="eligibility">

                        <h4>Règlement du tournoi</h4>

                        <p>
                            {{ $tournoi->regles ?? 'Aucun règlement disponible.' }}
                        </p>

                    </div>

                    <!-- BOUTON D'INSCRIPTION -->
                    @if (!$estInscrit)

                        <a
                            href="{{ route('tournament.registration', ['id' => $tournoi->id]) }}"
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

            <!-- AUCUN TOURNOI -->
            <div class="empty">

                <p>
                    Aucun tournoi n'est disponible pour cette catégorie
                    pour le moment.
                </p>

                <p>
                    Reviens bientôt pour découvrir les prochains tournois !
                </p>

                <a href="{{ route('home') }}" class="btn-register">
                    Retour à l'accueil
                </a>

            </div>

        @endforelse

    </main>

    <!-- PIED DE PAGE -->
    <footer class="footer">

        <p>
            &copy; {{ date('Y') }} - Sport &amp; Tournois
        </p>

    </footer>

</body>
</html>
