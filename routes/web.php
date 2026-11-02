<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;


// ============================================
// ACCUEIL : REDIRECTION VERS LA CONNEXION
// ============================================

Route::get('/', function () {

    if (Auth::check()) {
        return redirect('/home');
    }

    return redirect('/login');
});


// ============================================
// PAGE D'ACCUEIL : CATÉGORIES
// ============================================

Route::get('/home', function (Request $request) {

    $utilisateur = $request->user();

    if (!$utilisateur) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour accéder aux tournois.'
        );
    }

    if (!$utilisateur->est_actif) {
        Auth::logout();

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

    $utilisateur = $request->user();

    if (!$utilisateur) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour accéder aux tournois.'
        );
    }

    if (!$utilisateur->est_actif) {
        Auth::logout();

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

Route::get('/login', function (Request $request) {

    if ($request->user()?->est_actif) {
        return redirect('/home');
    }

    Auth::logout();

    return view('login');
});


// ============================================
// TRAITEMENT DE LA CONNEXION
// ============================================

Route::post('/login', [UserController::class, 'login']);


// ============================================
// DÉCONNEXION
// ============================================

Route::post('/logout', function (Request $request) {

    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

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

    $utilisateur = $request->user();

    if (!$utilisateur) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour participer à un tournoi.'
        );
    }

    $utilisateurId = $utilisateur->id;

    if (!$utilisateur->est_actif) {
        Auth::logout();

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

    $utilisateur = $request->user();

    if (!$utilisateur) {
        return redirect('/login')->with(
            'error',
            'Connecte-toi pour participer à un tournoi.'
        );
    }

    $utilisateurId = $utilisateur->id;

    if (!$utilisateur->est_actif) {
        Auth::logout();

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

Route::post('/register', [UserController::class, 'create_user']);
