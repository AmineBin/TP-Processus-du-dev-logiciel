<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Créer un tournoi</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tournament.css') }}">
</head>
<body class="tournament-page">
<header class="header">
    <h1>Créer un tournoi</h1>
    <p>Une discipline, des règles et un rendez-vous pour les joueurs.</p>
</header>

<main class="container">
    <a class="back" href="{{ route('home') }}">Retour aux catégories</a>

    @if (session('error'))
        <p class="tournament-message" role="alert">{{ session('error') }}</p>
    @endif

    @if ($errors->any())
        <div class="tournament-message" role="alert">
            <strong>Quelques informations sont à corriger.</strong>
            <ul>
                @foreach ($errors->getMessages() as $field => $messages)
                    <li><a href="#{{ $field }}">{{ $messages[0] }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($categories->isEmpty() || $statuts->isEmpty())
        <p class="tournament-message" role="alert">Les disciplines ou les statuts ne sont pas encore disponibles. Contacte l’administrateur.</p>
    @endif

    <form method="POST" action="{{ route('tournaments.store') }}" class="card form-card tournament-form">
        @csrf
        <p class="tournament-intro">Les champs sont obligatoires, sauf mention « facultatif ».</p>

        <div class="tournament-wide">
            <label for="nom">Nom du tournoi</label>
            <input type="text" id="nom" name="nom" value="{{ old('nom') }}" maxlength="100" placeholder="Ex. Coupe du campus" required aria-invalid="{{ $errors->has('nom') ? 'true' : 'false' }}">
        </div>

        <div>
            <label for="categorie_id">Discipline</label>
            <select id="categorie_id" name="categorie_id" required aria-invalid="{{ $errors->has('categorie_id') ? 'true' : 'false' }}">
                <option value="" disabled @selected(!old('categorie_id'))>Choisir une discipline</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected((string) old('categorie_id') === (string) $categorie->id)>{{ $categorie->nom }} — {{ $categorie->parent?->nom }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="statut_id">Statut</label>
            <select id="statut_id" name="statut_id" required aria-invalid="{{ $errors->has('statut_id') ? 'true' : 'false' }}">
                <option value="" disabled @selected(!old('statut_id', $statuts->firstWhere('code', 'A_VENIR')?->id))>Choisir un statut</option>
                @foreach ($statuts as $statut)
                    <option value="{{ $statut->id }}" @selected((string) old('statut_id', $statuts->firstWhere('code', 'A_VENIR')?->id) === (string) $statut->id)>{{ $statut->libelle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="description">Description <span>(facultatif)</span></label>
            <textarea id="description" name="description" rows="3" maxlength="5000" placeholder="Présente le format et l’ambiance de la rencontre." aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}">{{ old('description') }}</textarea>
        </div>

        <div>
            <label for="regles">Règles <span>(facultatif)</span></label>
            <textarea id="regles" name="regles" rows="3" maxlength="5000" placeholder="Matériel, déroulement, consignes de participation…" aria-invalid="{{ $errors->has('regles') ? 'true' : 'false' }}">{{ old('regles') }}</textarea>
        </div>

        <div>
            <label for="date_debut">Début du tournoi</label>
            <input type="datetime-local" id="date_debut" name="date_debut" value="{{ old('date_debut') }}" required aria-invalid="{{ $errors->has('date_debut') ? 'true' : 'false' }}">
        </div>

        <div>
            <label for="date_fin">Fin du tournoi</label>
            <input type="datetime-local" id="date_fin" name="date_fin" value="{{ old('date_fin') }}" required aria-invalid="{{ $errors->has('date_fin') ? 'true' : 'false' }}">
        </div>

        <div>
            <label for="date_limite_inscription">Clôture des inscriptions</label>
            <input type="datetime-local" id="date_limite_inscription" name="date_limite_inscription" value="{{ old('date_limite_inscription') }}" required aria-invalid="{{ $errors->has('date_limite_inscription') ? 'true' : 'false' }}">
        </div>

        <div>
            <label for="nombre_max_participants">Nombre maximum de participants</label>
            <input type="number" id="nombre_max_participants" name="nombre_max_participants" value="{{ old('nombre_max_participants') }}" min="1" max="2147483647" step="1" placeholder="Ex. 16" required aria-invalid="{{ $errors->has('nombre_max_participants') ? 'true' : 'false' }}">
        </div>

        <button type="submit" @disabled($categories->isEmpty() || $statuts->isEmpty())>Créer le tournoi</button>
    </form>
</main>
</body>
</html>
