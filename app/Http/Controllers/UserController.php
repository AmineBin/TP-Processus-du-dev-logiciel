<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ErrorHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** Orchestre l’authentification ; validation et contrôle d’accès restent séparés. */
class UserController extends Controller
{
    /** Oriente le visiteur vers la connexion ou l’accueil selon sa session. */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user() ? 'home' : 'login');
    }

    /** Affiche le formulaire de connexion, réservé aux visiteurs. */
    public function showLoginForm(): View
    {
        return view('login');
    }

    /** Authentifie les données déjà validées par LoginRequest. */
    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        // Même message pour un compte absent, désactivé ou un mauvais mot de passe.
        if (! Auth::attempt([...$request->validated(), 'est_actif' => true])) {
            return ErrorHandler::respond(
                $request,
                ErrorHandler::AUTH_INVALID_CREDENTIALS,
                401,
                'login',
                ['email' => [ErrorHandler::message(ErrorHandler::AUTH_INVALID_CREDENTIALS)]],
                $request->only('email'),
            );
        }

        // Empêche la réutilisation d’un identifiant de session connu avant connexion.
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Connexion réussie.']);
        }

        return redirect()->intended(route('home'));
    }

    /** Affiche le formulaire de création d’un compte. */
    public function showRegistrationForm(): View
    {
        return view('register');
    }

    /** Crée un compte standard sans accepter de rôle fourni par le client. */
    public function register(RegisterRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        try {
            // Les IDs varient selon les bases ; le code métier reste stable.
            $role = Role::query()->where('code', Role::USER_CODE)->first();

            if (! $role) {
                return ErrorHandler::respond($request, ErrorHandler::AUTH_ROLE_UNAVAILABLE, 503, 'register');
            }

            $user = new User([
                'nom_utilisateur' => $data['username'],
                'nom' => $data['last_name'] ?? null,
                'prenom' => $data['first_name'] ?? null,
                'email' => $data['email'],
                'numero_telephone' => $data['phone'],
                'date_naissance' => $data['birth_date'],
                'mot_de_passe_hash' => Hash::make($data['password']),
            ]);
            $user->role()->associate($role);
            $user->save();
        } catch (UniqueConstraintViolationException $exception) {
            // La contrainte SQL protège aussi contre deux inscriptions simultanées.
            return ErrorHandler::respond($request, ErrorHandler::AUTH_ACCOUNT_CONFLICT, 409, 'register');
        } catch (QueryException $exception) {
            // Les détails techniques restent dans les logs, jamais dans la réponse.
            report($exception);

            return ErrorHandler::respond($request, ErrorHandler::AUTH_REGISTRATION_FAILED, 503, 'register');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Compte créé avec succès.'], 201);
        }

        return redirect()->route('login')->with('success', 'Compte créé avec succès.');
    }

    /** Supprime l’authentification, les données de session et l’ancien jeton CSRF. */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tu as été déconnecté.']);
        }

        return redirect()->route('login')->with('success', 'Tu as été déconnecté.');
    }
}
