
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;


// ============================================
// ACCUEIL : REDIRECTION VERS LA CONNEXION
// ============================================

Route::get('/', function () {

    if (session()->has('utilisateur_id')) {
        return redirect('/home');
    }

    return redirect('/login');
});


// ============================================
// PAGE D'ACCUEIL : CATÉGORIES
// ============================================

Route::get('/home', function (Request $request) {

    $utilisateurId = $request->session()->get('utilisateur_id');

    if (!$utilisateurId) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour accéder aux tournois.'
        );
    }

    $utilisateur = DB::table('utilisateur')
        ->where('id', $utilisateurId)
        ->where('est_actif', true)
        ->first();

    if (!$utilisateur) {
        $request->session()->forget('utilisateur_id');

        return redirect('/login')->with(
            'error',
            'Ton compte est introuvable ou désactivé.'
        );
    }

    $categories = DB::table('categorie')
        ->whereNull('categorie_parent_id')
        ->get();

    foreach ($categories as $categorie) {

        $categorie->sous_categories = DB::table('categorie')
            ->where('categorie_parent_id', $categorie->id)
            ->get();
    }

    $model = new class
    {
        public function urlName(?string $name): string
        {
            return Str::slug($name ?? '');
        }
    };

    return view('home', compact('categories', 'model'));
});


// ============================================
// PAGE DES TOURNOIS D'UNE CATÉGORIE
// ============================================

Route::get('/sport/{name?}', function (
    Request $request,
    ?string $name = null
) {

    $utilisateurId = $request->session()->get('utilisateur_id');

    if (!$utilisateurId) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour accéder aux tournois.'
        );
    }

    $utilisateur = DB::table('utilisateur')
        ->where('id', $utilisateurId)
        ->where('est_actif', true)
        ->first();

    if (!$utilisateur) {
        $request->session()->forget('utilisateur_id');

        return redirect('/login')->with(
            'error',
            'Ton compte est introuvable ou désactivé.'
        );
    }

    $name = $name ?? 'Sports';

    $categorie = DB::table('categorie')
        ->get()
        ->first(function ($c) use ($name) {
            return Str::slug($c->nom) === Str::slug($name);
        });

    $categories = collect();
    $tournois = collect();

    if ($categorie) {

        $categories = DB::table('categorie')
            ->where('categorie_parent_id', $categorie->id)
            ->get();

        if ($categories->isNotEmpty()) {
            $idsCategories = $categories->pluck('id')->toArray();
        } else {
            $idsCategories = [$categorie->id];
        }

        $tournois = DB::table('tournoi')
            ->whereIn('categorie_id', $idsCategories)
            ->orderBy('date_debut')
            ->get();
    }

    return view('sport', [
        'name' => $categorie->nom ?? $name,
        'categories' => $categories,
        'tournois' => $tournois
    ]);
});


// ============================================
// PAGE DE CONNEXION
// ============================================

Route::get('/login', function () {

    if (session()->has('utilisateur_id')) {

        $utilisateur = DB::table('utilisateur')
            ->where('id', session('utilisateur_id'))
            ->where('est_actif', true)
            ->first();

        if ($utilisateur) {
            return redirect('/home');
        }

        session()->forget('utilisateur_id');
    }

    return view('login');
});


// ============================================
// TRAITEMENT DE LA CONNEXION
// ============================================

Route::post('/login', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $utilisateur = DB::table('utilisateur')
        ->where('email', $request->email)
        ->first();

    if (
        !$utilisateur ||
        !Hash::check(
            $request->password,
            $utilisateur->mot_de_passe_hash
        )
    ) {

        return back()->withErrors([
            'email' => 'Adresse e-mail ou mot de passe incorrect.'
        ]);
    }

    if (!$utilisateur->est_actif) {

        return back()->withErrors([
            'email' => 'Ce compte est désactivé.'
        ]);
    }

    $request->session()->regenerate();

    $request->session()->put(
        'utilisateur_id',
        $utilisateur->id
    );

    return redirect('/home')->with(
        'success',
        'Connexion réussie !'
    );
});


// ============================================
// DÉCONNEXION
// ============================================

Route::post('/logout', function (Request $request) {

    $request->session()->forget('utilisateur_id');

    $request->session()->regenerateToken();

    $request->session()->invalidate();

    return redirect('/login')->with(
        'success',
        'Tu as été déconnecté.'
    );
});


// ============================================
// PAGE DE CONFIRMATION AVANT INSCRIPTION
// ============================================

Route::get('/tournoi/{id}/inscription', function (
    Request $request,
    $id
) {

    $utilisateurId = $request->session()->get('utilisateur_id');

    if (!$utilisateurId) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour participer à un tournoi.'
        );
    }

    $utilisateur = DB::table('utilisateur')
        ->where('id', $utilisateurId)
        ->where('est_actif', true)
        ->first();

    if (!$utilisateur) {
        $request->session()->forget('utilisateur_id');

        return redirect('/login')->with(
            'error',
            'Ton compte est introuvable ou désactivé.'
        );
    }

    $tournoi = DB::table('tournoi')
        ->where('id', $id)
        ->first();

    if (!$tournoi) {
        abort(404, 'Tournoi introuvable.');
    }

    $dejaInscrit = DB::table('inscription')
        ->where('utilisateur_id', $utilisateurId)
        ->where('tournoi_id', $id)
        ->exists();

    return view('inscription', [
        'tournoi' => $tournoi,
        'utilisateur' => $utilisateur,
        'dejaInscrit' => $dejaInscrit
    ]);
});


// ============================================
// ENREGISTRER L'INSCRIPTION AU TOURNOI
// ============================================

Route::post('/tournoi/{id}/inscription', function (
    Request $request,
    $id
) {

    $utilisateurId = $request->session()->get('utilisateur_id');

    if (!$utilisateurId) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour participer à un tournoi.'
        );
    }

    $utilisateur = DB::table('utilisateur')
        ->where('id', $utilisateurId)
        ->where('est_actif', true)
        ->first();

    if (!$utilisateur) {
        $request->session()->forget('utilisateur_id');

        return redirect('/login')->with(
            'error',
            'Ton compte est introuvable ou désactivé.'
        );
    }

    $tournoi = DB::table('tournoi')
        ->where('id', $id)
        ->first();

    if (!$tournoi) {
        abort(404, 'Tournoi introuvable.');
    }

    // Vérifier la date limite d'inscription

    if (now()->greaterThan($tournoi->date_limite_inscription)) {

        return back()->with(
            'error',
            'La date limite d’inscription est dépassée.'
        );
    }

    // Vérifier si l'utilisateur est déjà inscrit

    $dejaInscrit = DB::table('inscription')
        ->where('utilisateur_id', $utilisateurId)
        ->where('tournoi_id', $id)
        ->exists();

    if ($dejaInscrit) {

        return back()->with(
            'error',
            'Tu es déjà inscrit à ce tournoi.'
        );
    }

    // Vérifier le nombre de places disponibles

    $nombreInscrits = DB::table('inscription')
        ->where('tournoi_id', $id)
        ->count();

    if ($nombreInscrits >= $tournoi->nombre_max_participants) {

        return back()->with(
            'error',
            'Désolé, ce tournoi est complet.'
        );
    }

    // Enregistrer l'inscription

    DB::table('inscription')->insert([
        'utilisateur_id' => $utilisateurId,
        'tournoi_id' => $id,
        'date_inscription' => now(),
        'statut_id' => 1
    ]);

    return redirect('/tournoi/' . $id . '/inscription')
        ->with(
            'success',
            'Félicitations ! Ton inscription au tournoi a bien été confirmée.'
        );
});


// ============================================
// PAGE D'INSCRIPTION D'UN NOUVEAU COMPTE
// ============================================

// AFFICHER LE FORMULAIRE (GET)

Route::get('/register', function () {
    return view('register');
});


// ENREGISTRER LE NOUVEAU COMPTE (POST)

Route::post('/register', function (Request $request) {

    $request->validate([
        'nom_utilisateur' => 'required|string|max:100',
        'nom' => 'required|string|max:100',
        'prenom' => 'required|string|max:100',
        'email' => 'required|email|max:255|unique:utilisateur,email',
        'numero_telephone' => 'required|string|max:20',
        'date_naissance' => 'required|date|before:today',
        'password' => 'required|string|min:6|confirmed',
    ], [
        'email.unique' => 'Cette adresse mail possède déjà un compte.',
        'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        'date_naissance.before' => 'La date de naissance doit être dans le passé.',
    ]);

    DB::table('utilisateur')->insert([
        'nom_utilisateur' => $request->nom_utilisateur,
        'nom' => $request->nom,
        'prenom' => $request->prenom,
        'email' => $request->email,
        'numero_telephone' => $request->numero_telephone,
        'date_naissance' => $request->date_naissance,
        'mot_de_passe_hash' => Hash::make($request->password),
        'date_creation' => now(),
        'est_actif' => true,
        'role_id' => 1,
    ]);

    return redirect('/login')->with(
        'success',
        'Ton compte a bien été créé ! Tu peux maintenant te connecter.'
    );
});
