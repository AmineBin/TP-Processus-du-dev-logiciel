<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon profil</title>
    <link rel="stylesheet" href="{{ asset('css/profil.css') }}">
    <link rel="stylesheet" href="{{ asset('css/registrations.css') }}">
</head>
<body>
<header class="header">
    <h1>Mon profil</h1>
    <p>Retrouve tes informations personnelles et tes inscriptions.</p>
</header>

<main class="container">
    <a href="{{ route('home') }}" class="back">← Retour à l'accueil</a>
    <section class="section">
        <h2>Mes informations personnelles</h2>
        <div class="profile-card">
            <div class="profile-info">
                <div class="profile-item"><span class="label">Nom</span><span class="value">{{ $utilisateur->nom ?? 'Non renseigné' }}</span></div>
                <div class="profile-item"><span class="label">Prénom</span><span class="value">{{ $utilisateur->prenom ?? 'Non renseigné' }}</span></div>
                <div class="profile-item"><span class="label">Pseudo</span><span class="value">{{ $utilisateur->nom_utilisateur }}</span></div>
                <div class="profile-item"><span class="label">Adresse e-mail</span><span class="value">{{ $utilisateur->email }}</span></div>
                <div class="profile-item"><span class="label">Téléphone</span><span class="value">{{ $utilisateur->numero_telephone }}</span></div>
                <div class="profile-item"><span class="label">Date de naissance</span><span class="value">{{ $utilisateur->date_naissance?->format('d/m/Y') ?? 'Non renseignée' }}</span></div>
                <div class="profile-item"><span class="label">Âge</span><span class="value">{{ $age !== null ? $age.' ans' : 'Non renseigné' }}</span></div>
            </div>
        </div>
    </section>

    <section class="section">
        <h2>Mes tournois</h2>
        <p class="registration-notice">{{ $inscriptionsActives }} inscription(s) active(s). Les participations annulées restent dans ton historique.</p>
        <div class="tournament-list">
        @forelse ($inscriptions as $inscription)
            <article class="tournament-card">
                <h3>{{ $inscription->tournament->nom }}</h3>
                <p><strong>Date de début :</strong> {{ $inscription->tournament->date_debut->format('d/m/Y à H:i') }}</p>
                <p><strong>Date de fin :</strong> {{ $inscription->tournament->date_fin?->format('d/m/Y à H:i') ?? 'Non définie' }}</p>
                <p><strong>Catégorie :</strong> {{ $inscription->tournament->category?->nom ?? 'Non renseignée' }}</p>
                @include('partials.registration-status', ['inscription' => $inscription])
                <p><a class="back" href="{{ route('tournament.registration', ['id' => $inscription->tournoi_id]) }}">Voir le tournoi et mon inscription</a></p>
            </article>
        @empty
            <div class="empty">
                <p>Tu n'es inscrit(e) à aucun tournoi pour le moment.</p>
                <a href="{{ route('sport') }}" class="btn-profile">Découvrir les tournois</a>
            </div>
        @endforelse
        </div>
    </section>

    <section class="section">
        <h2>Mon compte</h2>
        <div class="button-row">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Se déconnecter</button>
            </form>
        </div>
    </section>
</main>
<footer class="footer"><p>© {{ date('Y') }} - Plateforme de tournois</p></footer>
</body>
</html>
