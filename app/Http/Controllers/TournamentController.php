<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TournamentController
{
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user === null || ! DB::table('role_utilisateur')
            ->where('id', $user->role_id)
            ->where('code', 'ORGANISATEUR')
            ->exists()) {
            abort(403, 'Seuls les organisateurs peuvent créer un tournoi.');
        }

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'regles' => ['nullable', 'string'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'date_limite_inscription' => ['nullable', 'date', 'before_or_equal:date_debut'],
            'nombre_max_participants' => ['nullable', 'integer', 'min:1'],
            'categorie_id' => ['required', 'integer', 'exists:categorie,id'],
            'statut_id' => ['required', 'integer', 'exists:statut_tournoi,id'],
        ]);

        DB::table('tournoi')->insert([
            ...$data,
            'organisateur_id' => $user->id,
        ]);

        return redirect('/tournament')->with('success', 'Tournoi créé avec succès.');
    }
}
