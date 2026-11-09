<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public function index(?string $name = null)
    {
        // Récupérer le nom de la catégorie
        $name = $name ?? 'Tous les sports';

        // Récupérer l'identifiant de l'utilisateur connecté
        $utilisateurId = Auth::id();

        // Récupérer les tournois et les inscriptions de l'utilisateur
        $tournois = DB::table('tournoi')
            ->join('categorie', 'tournoi.categorie_id', '=', 'categorie.id')
            ->leftJoin('inscription', function ($join) use ($utilisateurId) {
                $join->on('tournoi.id', '=', 'inscription.tournoi_id')
                    ->where('inscription.utilisateur_id', '=', $utilisateurId);
            })
            ->select(
                'tournoi.*',
                'categorie.nom as categorie_nom',
                'inscription.id as inscription_tournoi_id'
            )
            ->when($name !== 'Tous les sports', function ($query) use ($name) {
                $query->where('categorie.nom', $name);
            })
            ->orderBy('tournoi.date_debut', 'asc')
            ->get();

        return view('sport', compact('name', 'tournois'));
    }
}
