<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Profil du compte de la session ; aucun identifiant de tiers n’est accepté. */
class ProfilController extends Controller
{
    /** Charge les informations personnelles et l’historique avec les statuts de référence. */
    public function index(Request $request): View
    {
        $utilisateur = $request->user();
        $inscriptions = $utilisateur->registrations()
            ->with(['tournament.category', 'status'])->orderByDesc('date_inscription')->get();

        return view('profil', [
            'utilisateur' => $utilisateur,
            'age' => $utilisateur->date_naissance?->age,
            'inscriptions' => $inscriptions,
            'inscriptionsActives' => $inscriptions->filter(fn ($inscription) => $inscription->isActive())->count(),
        ]);
    }
}
