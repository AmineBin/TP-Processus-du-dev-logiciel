
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Inscription au tournoi - {{ $tournoi->nom }}</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

<header class="header">

    {{-- Logo du profil --}}
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

    <h1>Inscription au tournoi</h1>

    <p>Confirme ta participation à ce tournoi.</p>

</header>


<main class="container">

    <a class="back" href="{{ url('/home') }}">
        ← Retour à l'accueil
    </a>


    {{-- MESSAGE D'ERREUR --}}

    @if (session('error'))

        <div class="error">
            {{ session('error') }}
        </div>

    @endif


    {{-- INFORMATIONS DU TOURNOI --}}

    <article class="tournament-card">

        <div class="tournament-banner">
            {{ strtoupper($tournoi->nom) }}
        </div>

        <div class="tournament-content">

            <h2>{{ $tournoi->nom }}</h2>

            <p class="description">
                {{ $tournoi->description }}
            </p>


            <h3>Informations du tournoi</h3>

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


            {{-- SITUATION DE L'INSCRIPTION --}}

            @if (session('success'))

                <div class="registration-summary">

                    <h2>Félicitations !</h2>

                    <p class="registration-intro">
                        {{ session('success') }}
                    </p>

                    <span class="registration-confirmed">
                        ✓ Inscription confirmée
                    </span>

                    <div class="button-row">

                        <a href="{{ url('/profil') }}" class="btn-register">
                            Voir mon profil
                        </a>

                        <a href="{{ url('/home') }}" class="btn-secondary">
                            Retour à l'accueil
                        </a>

                    </div>

                </div>


            @elseif ($dejaInscrit)

                <div class="registration-summary">

                    <h2>Tu es déjà inscrite !</h2>

                    <p class="registration-intro">
                        Ton inscription à ce tournoi est déjà enregistrée.
                    </p>

                    <span class="registration-confirmed">
                        ✓ Inscription confirmée
                    </span>

                    <div class="button-row">

                        <a href="{{ url('/profil') }}" class="btn-register">
                            Voir mon profil
                        </a>

                        <a href="{{ url('/home') }}" class="btn-secondary">
                            Retour à l'accueil
                        </a>

                    </div>

                </div>


            @else

                {{-- FORMULAIRE DE CONFIRMATION --}}

                <div class="info-message">

                    <h3>Confirmer ton inscription</h3>

                    <p>
                        Vérifie tes informations avant de confirmer
                        définitivement ta participation.
                    </p>

                </div>

                <form
                    method="POST"
                    action="{{ url('/tournoi/' . $tournoi->id . '/inscription') }}"
                >

                    @csrf

                    <button type="submit" class="btn-register">
                        Confirmer mon inscription
                    </button>

                </form>

            @endif

        </div>

    </article>

</main>


<footer class="footer">

    <p>© {{ date('Y') }} - Sport &amp; Tournois</p>

</footer>

</body>
</html>
