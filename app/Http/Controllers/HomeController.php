<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/** Présentation des catégories ; aucun traitement d’authentification ici. */
class HomeController extends Controller
{
    /** Charge les catégories racines et leurs sous-catégories pour l’accueil. */
    public function index(): View
    {
        $categories = DB::table('categorie')->whereNull('categorie_parent_id')->get();

        foreach ($categories as $categorie) {
            $categorie->sous_categories = DB::table('categorie')
                ->where('categorie_parent_id', $categorie->id)
                ->get();
        }

        return view('home', compact('categories'));
    }
}
