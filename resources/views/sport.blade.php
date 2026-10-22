<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $name }} - Tournois</title>

    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

<header class="header">
    <h1>Tournois de {{ $name }}</h1>
    <p>Découvre les tournois disponibles et inscris ton équipe !</p>
</header>

<main class="container">

    <a class="back" href="/">Retour à l'accueil</a>

    @if (in_array(strtolower($name), ['football', 'foot'], true))

        <h2>Tournois de football disponibles</h2>

        <article class="tournament-card">

            <div class="tournament-banner football-banner">
                TOURNOI DE FOOTBALL À 7
            </div>

            <div class="tournament-content">

                <span class="status">Inscriptions ouvertes</span>

                <h3>Avignon Football Cup</h3>

                <p class="description">
                    Participe à l'Avignon Football Cup et affronte
                    d'autres équipes dans une ambiance sportive
                    et conviviale. Prépare ton équipe et tente
                    de remporter le trophée !
                </p>

                <h4>Informations du tournoi</h4>

                <ul class="tournament-info">
                    <li><strong>Date :</strong> 22 novembre 2026</li>
                    <li><strong>Horaires :</strong> 9 h à 18 h</li>
                    <li><strong>Lieu :</strong> Complexe sportif, Avignon</li>
                    <li><strong>Sport :</strong> Football à 7</li>
                    <li><strong>Catégorie :</strong> Seniors, à partir de 18 ans</li>
                    <li><strong>Joueurs :</strong> 7 joueurs sur le terrain par équipe</li>
                    <li><strong>Remplaçants :</strong> Jusqu'à 5 par équipe</li>
                    <li><strong>Frais d'inscription :</strong> 70 € par équipe</li>
                    <li><strong>Récompense :</strong> Un trophée pour l'équipe gagnante</li>
                    <li><strong>Date limite d'inscription :</strong> 15 novembre 2026</li>
                </ul>

                <div class="eligibility">
                    <h4>Conditions de participation</h4>

                    <ul>
                        <li>Être âgé de 18 ans ou plus.</li>
                        <li>Constituer une équipe de football à 7.</li>
                        <li>Prévoir des équipements de football adaptés.</li>
                        <li>Respecter le règlement du tournoi.</li>
                        <li>Régler les frais d'inscription avant la date limite.</li>
                    </ul>
                </div>

                <div class="eligibility">
                    <h4>Programme de la journée</h4>

                    <ul>
                        <li>8 h 30 : accueil des équipes.</li>
                        <li>9 h : début des matchs de poule.</li>
                        <li>13 h : reprise des matchs.</li>
                        <li>16 h : demi-finales.</li>
                        <li>17 h : finale.</li>
                        <li>17 h 45 : remise du trophée.</li>
                    </ul>
                </div>

                <a href="/inscription-football.php" class="btn-register">
                    S'inscrire au tournoi
                </a>

            </div>
        </article>

    @elseif (in_array(strtolower($name), ['basketball', 'basket'], true))

        <h2>Tournois de basketball disponibles</h2>

        <article class="tournament-card">

            <div class="tournament-banner">
                TOURNOI DE BASKETBALL 3x3
            </div>

            <div class="tournament-content">

                <span class="status">Inscriptions ouvertes</span>

                <h3>Avignon Basket Challenge</h3>

                <p class="description">
                    Participe à un tournoi de basketball 3x3
                    et affronte d'autres équipes dans une
                    ambiance sportive et conviviale.
                </p>

                <h4>Informations du tournoi</h4>

                <ul class="tournament-info">
                    <li><strong>Date :</strong> 15 novembre 2026</li>
                    <li><strong>Horaires :</strong> 9 h à 18 h</li>
                    <li><strong>Lieu :</strong> Gymnase municipal, Avignon</li>
                    <li><strong>Catégorie :</strong> U18, de 16 à 17 ans</li>
                    <li><strong>Format :</strong> 3 joueurs par équipe</li>
                    <li><strong>Frais d'inscription :</strong> 15 € par équipe</li>
                    <li><strong>Date limite d'inscription :</strong> 8 novembre 2026</li>
                </ul>

                <div class="eligibility">
                    <h4>Conditions de participation</h4>

                    <ul>
                        <li>Être âgé de 16 à 17 ans.</li>
                        <li>Former une équipe de 3 joueurs.</li>
                        <li>Fournir une autorisation parentale.</li>
                        <li>Respecter le règlement du tournoi.</li>
                    </ul>
                </div>

                <a href="../Vue/inscription-basket.php" class="btn-register">
                    S'inscrire au tournoi
                </a>

            </div>
        </article>

    @else

        <h2>Tournois disponibles</h2>

        <p class="empty">
            Aucun tournoi n'est disponible pour cette catégorie
            pour le moment. Reviens bientôt pour découvrir
            les prochains tournois.
        </p>

    @endif

</main>

<footer class="footer">
    <p>© 2026 - Sport &amp; Tournois</p>
</footer>

</body>
</html>
