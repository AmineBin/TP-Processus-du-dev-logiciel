
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($name) ?> - Tournois</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="header">
        <h1><?= htmlspecialchars($name) ?></h1>
    </header>

    <main class="container">
        <a class="back" href="/">← Retour</a>
        <p class="empty">La liste des tournois de cette catégorie s'affichera ici.</p>
    </main>
</body>
</html>
