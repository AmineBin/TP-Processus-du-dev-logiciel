<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

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

        try {
            DB::table('tournoi')->insert([
                ...$data,
                'organisateur_id' => $user->id,
            ]);
        } catch (QueryException $exception) {
            Log::error('La création du tournoi a échoué.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Le tournoi n’a pas pu être enregistré. Vérifiez les données puis réessayez.');
        }

        return redirect('/tournament')->with('success', 'Tournoi créé avec succès.');
    }
}
