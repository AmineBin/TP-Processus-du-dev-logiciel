<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un tournoi</title>

    <link rel="stylesheet" href="/css/login.css">
</head>

<body>

<header class="header">
    <h1>Créer un tournoi</h1>
</header>

<main class="container">
    @if (session('success'))
        <p class="form-message form-message-success">{{ session('success') }}</p>
    @endif

    @if (session('error'))
        <p class="form-message form-message-error">{{ session('error') }}</p>
    @endif

    @if ($errors->any())
        <div class="form-message form-message-error">
            <p>Le tournoi n'a pas été créé :</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="/tournament" class="card form-card">
        @csrf

        <div class="form-field-wide">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" maxlength="100" placeholder="5 vs 5 CS:GO" required>
        </div>

        <div class="form-field-wide">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Match à mort par équipe en 5 vs 5"></textarea>
        </div>

        <div class="form-field-wide">
            <label for="regles">Règles</label>
            <textarea id="regles" name="regles" rows="4" placeholder="Règles du tournoi"></textarea>
        </div>

        <div class="form-field">
            <label for="date_debut">Date de début</label>
            <input type="datetime-local" id="date_debut" name="date_debut" required>
        </div>

        <div class="form-field">
            <label for="date_fin">Date de fin</label>
            <input type="datetime-local" id="date_fin" name="date_fin">
        </div>

        <div class="form-field">
            <label for="date_limite_inscription">Date limite d'inscription</label>
            <input type="datetime-local" id="date_limite_inscription" name="date_limite_inscription">
        </div>

        <div class="form-field">
            <label for="nombre_max_participants">Nombre maximum de participants</label>
            <input type="number" id="nombre_max_participants" name="nombre_max_participants" min="1">
        </div>

        <div class="form-field">
            <label for="categorie_id">Catégorie</label>
            <select id="categorie_id" name="categorie_id" required>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}">{{ $categorie->nom }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="statut_id">Statut</label>
            <select id="statut_id" name="statut_id" required>
                @foreach ($statuts as $statut)
                    <option value="{{ $statut->id }}">{{ $statut->libelle }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit">Créer le tournoi</button>
    </form>

    
</main>

</body>
</html>