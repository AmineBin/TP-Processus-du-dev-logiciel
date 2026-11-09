<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class ProfilController extends Controller
{
    public function index()
    {
        // Récupérer l'utilisateur connecté
        $utilisateur = Auth::user();

        // Calculer son âge
        $age = $utilisateur->date_naissance
            ? $utilisateur->date_naissance->age
            : null;

        // Récupérer ses tournois
        $tournois = $utilisateur->tournois()
            ->with('categorie')
            ->orderBy('date_debut', 'desc')
            ->get();

        // Afficher la page profil
        return view('profil', compact(
            'utilisateur',
            'age',
            'tournois'
        ));
    }
}
