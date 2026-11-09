
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mon profil</title>

    <link rel="stylesheet" href="{{ asset('css/profil.css') }}">
</head>

<body>

    <!-- ========================================
         EN-TÊTE
    ========================================= -->

    <header class="header">
        <h1>Mon profil</h1>
        <p>Retrouve tes informations personnelles et tes tournois.</p>
    </header>


    <main class="container">

        <!-- LIEN RETOUR -->

        <a href="{{ route('home') }}" class="back">
            ← Retour à l'accueil
        </a>


        <!-- ========================================
             INFORMATIONS PERSONNELLES
        ========================================= -->

        <section class="section">

            <h2>Mes informations personnelles</h2>

            <div class="profile-card">

                <div class="profile-info">

                    <div class="profile-item">
                        <span class="label">Nom</span>
                        <span class="value">
                            {{ $utilisateur->nom ?? 'Non renseigné' }}
                        </span>
                    </div>

                    <div class="profile-item">
                        <span class="label">Prénom</span>
                        <span class="value">
                            {{ $utilisateur->prenom ?? 'Non renseigné' }}
                        </span>
                    </div>

                    <div class="profile-item">
                        <span class="label">Pseudo</span>
                        <span class="value">
                            {{ $utilisateur->nom_utilisateur ?? 'Non renseigné' }}
                        </span>
                    </div>

                    <div class="profile-item">
                        <span class="label">Adresse e-mail</span>
                        <span class="value">
                            {{ $utilisateur->email ?? 'Non renseignée' }}
                        </span>
                    </div>

                    <div class="profile-item">
                        <span class="label">Téléphone</span>
                        <span class="value">
                            {{ $utilisateur->numero_telephone ?? 'Non renseigné' }}
                        </span>
                    </div>

                    <div class="profile-item">
                        <span class="label">Date de naissance</span>
                        <span class="value">
                            {{ $utilisateur->date_naissance
                                ? $utilisateur->date_naissance->format('d/m/Y')
                                : 'Non renseignée' }}
                        </span>
                    </div>

                    <div class="profile-item">
                        <span class="label">Âge</span>
                        <span class="value">
                            {{ $age !== null ? $age . ' ans' : 'Non renseigné' }}
                        </span>
                    </div>

                </div>

            </div>

        </section>


        <!-- ========================================
             MES INSCRIPTIONS
        ========================================= -->

        <section class="section">

            <h2>Mes tournois</h2>

            @if(isset($tournois) && $tournois->isNotEmpty())

                <div class="tournament-list">

                    @foreach($tournois as $tournoi)

                        <article class="tournament-card">

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

                            <p>
                                <strong>Catégorie :</strong>

                                {{ $tournoi->categorie?->nom ?? 'Non renseignée' }}
                            </p>

                        </article>

                    @endforeach

                </div>

            @else

                <div class="empty">

                    <p>Tu n'es inscrit(e) à aucun tournoi pour le moment.</p>

                    <a href="{{ route('home') }}" class="btn-profile">
                        Découvrir les tournois
                    </a>

                </div>

            @endif

        </section>


        <!-- ========================================
             DÉCONNEXION
        ========================================= -->

        <section class="section">

            <h2>Mon compte</h2>

            <div class="button-row">

                <form action="{{ route('logout') }}" method="POST">
                    @csrf

                    <button type="submit" class="btn-logout">
                        Se déconnecter
                    </button>

                </form>

            </div>

        </section>

    </main>


    <!-- ========================================
         PIED DE PAGE
    ========================================= -->

    <footer class="footer">
        <p>&copy; {{ date('Y') }} - Plateforme de tournois</p>
    </footer>

</body>
</html>
