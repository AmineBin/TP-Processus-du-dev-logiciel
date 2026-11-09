<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} - Tournois</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/registrations.css') }}">
</head>
<body>
<header class="header">
    @include('partials.profile-link')
    <h1>{{ $category ? 'Tournois de '.$name : $name }}</h1>
    <p>Découvre les tournois disponibles et inscris-toi !</p>
</header>

<main class="container">
    <a class="back" href="{{ route('home') }}">← Retour à l'accueil</a>

    @if (session('success'))
        <p class="success" role="status">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="error" role="alert">{{ session('error') }}</p>
    @endif

    <section class="registration-summary">
        <h2>Mes inscriptions dans cette catégorie</h2>
        @if ($mesInscriptions->isNotEmpty())
            <p class="registration-intro">{{ $mesInscriptions->count() }} inscription(s) active(s), en attente ou confirmée(s).</p>
            <div class="registration-list">
                @foreach ($mesInscriptions as $tournoi)
                    <article class="registration-item">
                        <h3>{{ $tournoi->nom }}</h3>
                        <p><strong>Date de début :</strong> {{ $tournoi->date_debut->format('d/m/Y à H:i') }}</p>
                        @include('partials.registration-status', ['inscription' => $inscriptions->get($tournoi->id)])
                        <p><a href="{{ route('tournament.registration', ['id' => $tournoi->id]) }}">Voir mon inscription</a></p>
                    </article>
                @endforeach
            </div>
        @else
            <p class="registration-empty">Tu n'es inscrit(e) à aucun tournoi dans cette catégorie pour le moment.</p>
        @endif
    </section>

    <h2>Tournois disponibles</h2>
    @forelse ($tournois as $tournoi)
        @php
            $inscription = $inscriptions->get($tournoi->id);
            $complet = $tournoi->nombre_max_participants !== null && $tournoi->active_registrations_count >= $tournoi->nombre_max_participants;
        @endphp
        <article class="tournament-card">
            <div class="tournament-banner">{{ strtoupper($tournoi->nom) }}</div>
            <div class="tournament-content">
                @if ($inscription)
                    @include('partials.registration-status', ['inscription' => $inscription])
                @endif
                <h3>{{ $tournoi->nom }}</h3>
                <p class="description">{{ $tournoi->description ?? 'Aucune description disponible.' }}</p>
                <h4>Informations du tournoi</h4>
                <ul class="tournament-info">
                    <li><strong>Discipline :</strong> {{ $tournoi->category?->nom ?? 'Non renseignée' }}</li>
                    <li><strong>Date de début :</strong> {{ $tournoi->date_debut->format('d/m/Y à H:i') }}</li>
                    <li><strong>Date de fin :</strong> {{ $tournoi->date_fin?->format('d/m/Y à H:i') ?? 'Non définie' }}</li>
                    <li><strong>Date limite d'inscription :</strong> {{ $tournoi->date_limite_inscription?->format('d/m/Y à H:i') ?? 'Au début du tournoi' }}</li>
                    <li><strong>Participants :</strong> {{ $tournoi->active_registrations_count }} / {{ $tournoi->nombre_max_participants ?? 'Non limité' }}</li>
                </ul>
                <div class="eligibility">
                    <h4>Règlement du tournoi</h4>
                    <p>{{ $tournoi->regles ?? 'Aucun règlement disponible.' }}</p>
                </div>

                @if ($inscription?->isActive())
                    <p class="already-registered">Ton inscription à ce tournoi est déjà enregistrée.</p>
                @elseif ($tournoi->registrationDeadlinePassed())
                    <p class="registration-notice">Les inscriptions à ce tournoi sont closes.</p>
                @elseif ($complet)
                    <p class="registration-notice">Ce tournoi est complet.</p>
                @elseif ($inscription && !$inscription->isCancelled())
                    <p class="registration-notice">Ce statut ne permet pas une nouvelle inscription.</p>
                @else
                    <a href="{{ route('tournament.registration', ['id' => $tournoi->id]) }}" class="btn-register">{{ $inscription?->isCancelled() ? 'Se réinscrire au tournoi' : "S'inscrire au tournoi" }}</a>
                @endif
            </div>
        </article>
    @empty
        <div class="empty">
            <p>Aucun tournoi n'est disponible pour cette catégorie pour le moment.</p>
            <a href="{{ route('home') }}" class="btn-register">Retour à l'accueil</a>
        </div>
    @endforelse
</main>
<footer class="footer"><p>© {{ date('Y') }} - Sport &amp; Tournois</p></footer>
</body>
</html>
