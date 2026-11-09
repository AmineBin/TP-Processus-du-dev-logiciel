
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

    <h1>Inscription au tournoi</h1>

    <p>Confirme ta participation à ce tournoi.</p>

</header>


<main class="container">


    <a class="back" href="{{ url('/sport') }}">
        Retour aux tournois
    </a>


    {{-- Message de confirmation --}}

    @if (session('success'))

        <div class="success">

            <h2>Inscription confirmée !</h2>

            <p>
                {{ session('success') }}
            </p>

        </div>

    @endif


    {{-- Message d'erreur --}}

    @if (session('error'))

        <p class="error">
            {{ session('error') }}
        </p>

    @endif


    <article class="tournament-card">


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


            <h3>Informations du participant</h3>


            <p>

                <strong>Prénom et nom :</strong>

                {{ $utilisateur->prenom }} {{ $utilisateur->nom }}

            </p>


            <p>

                <strong>Nom d'utilisateur :</strong>

                {{ $utilisateur->nom_utilisateur }}

            </p>


            <p>

                <strong>Adresse e-mail :</strong>

                {{ $utilisateur->email }}

            </p>


            <hr>


            {{-- Affichage selon la situation --}}

            @if (session('success'))

                <h2>Félicitations !</h2>

                <p>
                    Tu es officiellement inscrit à ce tournoi.
                </p>


                <a class="btn-register" href="{{ url('/home') }}">
                    Retour à l'accueil
                </a>


            @elseif ($dejaInscrit)

                <p class="error">

                    Tu es déjà inscrit à ce tournoi.

                </p>


                <a class="btn-register" href="{{ url('/home') }}">
                    Retour à l'accueil
                </a>


            @else

                <h3>Confirmer ton inscription</h3>


                <p>

                    Vérifie tes informations avant de confirmer
                    définitivement ta participation.

                </p>


                <form
                    method="POST"
                    action="{{ route('tournament.registration.store', ['id' => $tournoi->id]) }}"
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

    <p>© 2026 - Sport &amp; Tournois</p>

</footer>


</body>
</html>
