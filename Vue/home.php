<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tournois</title>

    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

<header class="header">
    <h1>Tournois</h1>
    <p>Choisissez une catégorie</p>
</header>

<main class="container">

    <?php foreach ($categories as $type => $items): ?>

        <section class="section">

            <h2><?= htmlspecialchars($type) ?></h2>

            <ul class="grid">

                <?php foreach ($items as $name): ?>

                    <li class="card">
                        <a href="/sport/<?= htmlspecialchars($model->urlName($name)) ?>">
                            <?= htmlspecialchars($name) ?>
                        </a>
                    </li>

                <?php endforeach; ?>

            </ul>

        </section>

    <?php endforeach; ?>

</main>

</body>
</html>
