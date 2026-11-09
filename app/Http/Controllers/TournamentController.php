<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTournamentRequest;
use App\Models\Category;
use App\Models\RegistrationStatus;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
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

    /** Résout les slugs de l’accueil et enrichit la liste avec les seules inscriptions du compte. */
    public function index(Request $request, ?string $name = null): View
    {
        $category = null;
        $query = Tournament::query()->with('category')
            ->withCount(['registrations as active_registrations_count' => fn ($registrations) => $registrations->active()]);

        if ($name !== null) {
            $category = Category::query()->get()->first(
                fn (Category $candidate) => Str::slug($candidate->nom) === Str::slug($name),
            );
            abort_unless($category, 404, 'Catégorie introuvable.');
            $query->whereIn('categorie_id', $category->subtreeIds());
        }

        $tournois = $query->orderBy('date_debut')->get();
        $inscriptions = $request->user()->registrations()->with('status')
            ->whereIn('tournoi_id', $tournois->modelKeys())->get()->keyBy('tournoi_id');
        $mesInscriptions = $tournois->filter(fn (Tournament $tournoi) => $inscriptions->get($tournoi->id)?->isActive());

        return view('sport', [
            'name' => $category?->nom ?? 'Tous les tournois',
            'category' => $category,
            'tournois' => $tournois,
            'inscriptions' => $inscriptions,
            'mesInscriptions' => $mesInscriptions,
        ]);
    }

    /** Affiche la situation réelle et le formulaire seulement lorsque la participation est possible. */
    public function showRegistration(Request $request, int $id): View
    {
        $tournoi = Tournament::query()->with('category')
            ->withCount(['registrations as active_registrations_count' => fn ($registrations) => $registrations->active()])
            ->findOrFail($id);
        $inscription = $request->user()->registrations()->with('status')->where('tournoi_id', $id)->first();

        return view('inscription', [
            'tournoi' => $tournoi,
            'utilisateur' => $request->user(),
            'inscription' => $inscription,
            'dejaInscrit' => $inscription?->isActive() ?? false,
            'inscriptionsFermees' => $tournoi->registrationDeadlinePassed(),
            'tournoiComplet' => $tournoi->nombre_max_participants !== null
                && $tournoi->active_registrations_count >= $tournoi->nombre_max_participants,
        ]);
    }

    /** Inscrit ou réactive une participation annulée, sans doublon ni ID de statut fixe. */
    public function register(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $error = DB::transaction(function () use ($request, $id): ?string {
                // Sur MySQL, le verrou du tournoi sérialise les dernières places et les doublons.
                $tournoi = Tournament::query()->lockForUpdate()->findOrFail($id);
                $inscription = $request->user()->registrations()->with('status')->where('tournoi_id', $id)->first();

                if ($inscription?->isActive()) {
                    return ErrorHandler::TOURNAMENT_ALREADY_REGISTERED;
                }
                if ($tournoi->registrationDeadlinePassed()) {
                    return ErrorHandler::TOURNAMENT_REGISTRATION_CLOSED;
                }
                if ($tournoi->nombre_max_participants !== null
                    && $tournoi->registrations()->active()->count() >= $tournoi->nombre_max_participants) {
                    return ErrorHandler::TOURNAMENT_FULL;
                }

                $pending = RegistrationStatus::where('code', RegistrationStatus::PENDING)->first();
                if (! $pending || ($inscription && ! $inscription->isCancelled())) {
                    return ErrorHandler::TOURNAMENT_REGISTRATION_UNAVAILABLE;
                }

                // Réutiliser le pivot annulé conserve son ID et respecte l’unicité utilisateur/tournoi.
                $inscription ??= new TournamentRegistration;
                $inscription->user()->associate($request->user());
                $inscription->tournament()->associate($tournoi);
                $inscription->status()->associate($pending);
                $inscription->date_inscription = now();
                $inscription->save();

                return null;
            });
        } catch (QueryException $exception) {
            report($exception);
            $error = ErrorHandler::TOURNAMENT_REGISTRATION_FAILED;
        }

        if ($error !== null) {
            $status = in_array($error, [ErrorHandler::TOURNAMENT_REGISTRATION_UNAVAILABLE, ErrorHandler::TOURNAMENT_REGISTRATION_FAILED], true)
                ? 503 : 409;

            return ErrorHandler::respond($request, $error, $status, 'tournament.registration', routeParameters: ['id' => $id]);
        }

        $message = 'Inscription enregistrée, en attente de confirmation.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'redirect_url' => route('tournament.registration', ['id' => $id])]);
        }

        return redirect()->route('tournament.registration', ['id' => $id])->with('success', $message);
    }
}
