<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription au tournoi - {{ $tournoi->nom }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/registrations.css') }}">
</head>
<body>
<header class="header">
    @include('partials.profile-link')
    <h1>Inscription au tournoi</h1>
    <p>Retrouve ta participation ou confirme ton inscription.</p>
</header>

<main class="container">
    <a class="back" href="{{ route('sport', ['name' => $tournoi->category ? \Illuminate\Support\Str::slug($tournoi->category->nom) : null]) }}">← Retour aux tournois</a>

    @if (session('error'))
        <p class="error" role="alert">{{ session('error') }}</p>
    @endif
    @if (session('success'))
        <p class="success" role="status">{{ session('success') }}</p>
    @endif

    <article class="tournament-card">
        <div class="tournament-banner">{{ strtoupper($tournoi->nom) }}</div>
        <div class="tournament-content">
            <h2>{{ $tournoi->nom }}</h2>
            <p class="description">{{ $tournoi->description }}</p>
            <h3>Informations du tournoi</h3>
            <ul class="tournament-info">
                <li><strong>Date de début :</strong> {{ $tournoi->date_debut->format('d/m/Y à H:i') }}</li>
                <li><strong>Date de fin :</strong> {{ $tournoi->date_fin?->format('d/m/Y à H:i') ?? 'Non définie' }}</li>
                <li><strong>Date limite d'inscription :</strong> {{ $tournoi->date_limite_inscription?->format('d/m/Y à H:i') ?? 'Au début du tournoi' }}</li>
                <li><strong>Nombre maximum de participants :</strong> {{ $tournoi->nombre_max_participants ?? 'Non limité' }}</li>
            </ul>

            <h3>Informations du participant</h3>
            <p>{{ $utilisateur->nom_utilisateur }} — {{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p>
            <p>{{ $utilisateur->email }}</p>

            @if ($inscription)
                @include('partials.registration-status', ['inscription' => $inscription])
            @endif

            @if ($dejaInscrit)
                <div class="registration-summary">
                    <h3>Ton inscription est enregistrée</h3>
                    <p>Tu es déjà inscrit à ce tournoi.</p>
                    <p>Le badge indique si elle est en attente ou confirmée.</p>
                    <a href="{{ route('profil') }}" class="btn-register">Voir mon profil</a>
                </div>
            @elseif ($inscriptionsFermees)
                <p class="registration-notice">La date limite d’inscription est dépassée.</p>
            @elseif ($tournoiComplet)
                <p class="registration-notice">Désolé, ce tournoi est complet.</p>
            @elseif ($inscription && !$inscription->isCancelled())
                <p class="registration-notice">Ce statut ne permet pas une nouvelle inscription.</p>
            @else
                <h3>{{ $inscription?->isCancelled() ? 'Renouveler ton inscription' : 'Confirmer ton inscription' }}</h3>
                <p class="registration-notice">Vérifie tes informations. La participation sera enregistrée en attente de confirmation.</p>
                <form method="POST" action="{{ route('tournament.registration.store', ['id' => $tournoi->id]) }}">
                    @csrf
                    <button type="submit" class="btn-register">{{ $inscription?->isCancelled() ? 'Renouveler mon inscription' : 'Confirmer mon inscription' }}</button>
                </form>
            @endif
        </div>
    </article>
</main>
<footer class="footer"><p>© {{ date('Y') }} - Sport &amp; Tournois</p></footer>
</body>
</html>
