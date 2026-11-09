<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect('/home');
});

Route::get('/home', function () {
    $categories = [
        'Jeux Vidéo' => ['Combat', 'FPS', 'Speed-run'],
        'Sports individuels' => ['Tennis', 'Natation', 'Athlétisme'],
        'Sports collectifs' => ['Football', 'Basketball', 'Rugby'],
    ];

    $model = new class
    {
        public function urlName(string $name): string
        {
            return Str::slug($name);
        }
    };

    return view('home', compact('categories', 'model'));
});

Route::get('/sport/{name?}', function (?string $name = null) {
    return view('sport', ['name' => $name ?? 'Sports']);
});

Route::get('/login/', function (?string $name = null) {
    return view('login');
})->name('login');

Route::post('/login', [UserController::class, 'login']);

Route::get('/register', function () {
    return view('register');
});

Route::post('/register', [UserController::class, 'create_user']);

Route::get('/tournament', function () {
    abort_unless(
        Auth::check() && DB::table('role_utilisateur')
            ->where('id', Auth::user()->role_id)
            ->where('code', 'ORGANISATEUR')
            ->exists(),
        403,
        'Seuls les organisateurs peuvent accéder à la création de tournoi.'
    );

    return view('tournament', [
        'categories' => DB::table('categorie')->get(),
        'statuts' => DB::table('statut_tournoi')->get(),
    ]);
})->middleware('auth');

Route::post('/tournament', [TournamentController::class, 'store'])->middleware('auth');