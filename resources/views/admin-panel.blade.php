<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panneau d'administration</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<header class="header">
    <h1>Panneau d'administration</h1>
    <p>Demandes d'inscription en attente</p>
</header>

<main class="admin-panel">
    <section class="admin-card" aria-labelledby="registration-requests-title">
        <div class="admin-card-header">
            <h2 id="registration-requests-title">Demandes d'inscription</h2>
            <p>Consultez les utilisateurs souhaitant devenir organisateur de tournois.</p>
        </div>

        @php
            $demandes = [
                ['nom' => 'Alice Martin', 'email' => 'alice.martin@example.com'],
                ['nom' => 'Karim Benali', 'email' => 'karim.benali@example.com'],
                ['nom' => 'Léa Dubois', 'email' => 'lea.dubois@example.com'],
            ];
        @endphp

        <ul class="request-list">
            @foreach ($demandes as $demande)
                <li class="request-row">
                    <div class="user-details">
                        <div class="user-name">{{ $demande['nom'] }}</div>
                        <div class="user-email">{{ $demande['email'] }}</div>
                    </div>
                    <div class="request-actions" aria-label="Actions pour {{ $demande['nom'] }}">
                        <button type="button" class="button-accept">Accepter</button>
                        <button type="button" class="button-refuse">Refuser</button>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</main>

<footer class="footer">
    <p>© {{ date('Y') }} - Sport &amp; Tournois</p>
</footer>
</body>
</html>
