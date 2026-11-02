<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Traitements existants des tournois, extraits des routes sans refonte métier. */
class TournamentController extends Controller
{
    /** Affiche les tournois correspondant au slug d’une catégorie. */
    public function index(?string $name = null): View
    {
        $name = $name ?? 'Sports';
        $categorie = DB::table('categorie')->get()
            ->first(fn ($categorie) => Str::slug($categorie->nom) === Str::slug($name));
        $categories = collect();
        $tournois = collect();

        if ($categorie) {
            $categories = DB::table('categorie')
                ->where('categorie_parent_id', $categorie->id)->get();
            $idsCategories = $categories->isNotEmpty() ? $categories->pluck('id')->all() : [$categorie->id];
            $tournois = DB::table('tournoi')->whereIn('categorie_id', $idsCategories)
                ->orderBy('date_debut')->get();
        }

        return view('sport', [
            'name' => $categorie->nom ?? $name,
            'categories' => $categories,
            'tournois' => $tournois,
        ]);
    }

    /** Prépare la confirmation pour l’utilisateur garanti connecté par auth. */
    public function showRegistration(Request $request, int $id): View
    {
        $tournoi = DB::table('tournoi')->where('id', $id)->first();
        abort_unless($tournoi, 404, 'Tournoi introuvable.');

        $dejaInscrit = DB::table('inscription')
            ->where('utilisateur_id', $request->user()->id)
            ->where('tournoi_id', $id)->exists();

        return view('inscription', [
            'tournoi' => $tournoi,
            'utilisateur' => $request->user(),
            'dejaInscrit' => $dejaInscrit,
        ]);
    }

    /** Enregistre la participation après les vérifications métier existantes. */
    public function register(Request $request, int $id): RedirectResponse
    {
        $tournoi = DB::table('tournoi')->where('id', $id)->first();
        abort_unless($tournoi, 404, 'Tournoi introuvable.');
        $redirect = route('tournament.registration', ['id' => $id]);

        if (now()->greaterThan($tournoi->date_limite_inscription)) {
            return redirect($redirect)->with('error', 'La date limite d’inscription est dépassée.');
        }

        $dejaInscrit = DB::table('inscription')
            ->where('utilisateur_id', $request->user()->id)
            ->where('tournoi_id', $id)->exists();

        if ($dejaInscrit) {
            return redirect($redirect)->with('error', 'Tu es déjà inscrit à ce tournoi.');
        }

        $nombreInscrits = DB::table('inscription')->where('tournoi_id', $id)->count();

        if ($nombreInscrits >= $tournoi->nombre_max_participants) {
            return redirect($redirect)->with('error', 'Désolé, ce tournoi est complet.');
        }

        DB::table('inscription')->insert([
            'utilisateur_id' => $request->user()->id,
            'tournoi_id' => $id,
            'date_inscription' => now(),
            'statut_id' => 1,
        ]);

        return redirect($redirect)->with(
            'success',
            'Félicitations ! Ton inscription au tournoi a bien été confirmée.',
        );
    }
}
