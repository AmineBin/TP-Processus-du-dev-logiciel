<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTournamentRequest;
use App\Models\Category;
use App\Models\Tournament;
use App\Models\TournamentStatus;
use App\Support\ErrorHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Consultation, création et inscription aux tournois ; accès contrôlé par les middlewares. */
class TournamentController extends Controller
{
    /** Charge uniquement les disciplines feuilles et les statuts de référence. */
    public function create(): View
    {
        return view('tournament', [
            'categories' => Category::disciplines()->with('parent')->orderBy('nom')->get(),
            'statuts' => TournamentStatus::query()->orderBy('id')->get(),
        ]);
    }

    /** Enregistre les données validées en imposant l’organisateur de la session. */
    public function store(StoreTournamentRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $tournament = DB::transaction(function () use ($request): Tournament {
                $tournament = new Tournament($request->validated());
                $tournament->organizer()->associate($request->user());
                $tournament->save();

                return $tournament->load('category');
            });
        } catch (QueryException $exception) {
            // Journaliser la cause, sans exposer SQL ou traces même en mode debug.
            report($exception);

            return ErrorHandler::respond(
                $request,
                ErrorHandler::TOURNAMENT_CREATION_FAILED,
                503,
                'tournaments.create',
                oldInput: $request->validated(),
            );
        }

        $url = route('sport', ['name' => Str::slug($tournament->category->nom)]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tournoi créé avec succès.',
                'data' => ['id' => $tournament->getKey()],
                'redirect_url' => $url,
            ], 201);
        }

        return redirect($url)->with('success', 'Tournoi créé avec succès.');
    }

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
