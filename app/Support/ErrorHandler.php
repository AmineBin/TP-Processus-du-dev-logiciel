<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Catalogue des erreurs publiques : ne jamais y inclure de SQL ou de secrets. */
final class ErrorHandler
{
    public const AUTH_REQUIRED = 'AUTH_REQUIRED';

    public const AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';

    public const AUTH_INACTIVE = 'AUTH_INACTIVE';

    public const AUTH_ALREADY_AUTHENTICATED = 'AUTH_ALREADY_AUTHENTICATED';

    public const AUTH_ROLE_UNAVAILABLE = 'AUTH_ROLE_UNAVAILABLE';

    public const AUTH_REGISTRATION_FAILED = 'AUTH_REGISTRATION_FAILED';

    public const AUTH_ACCOUNT_CONFLICT = 'AUTH_ACCOUNT_CONFLICT';

    public const AUTH_RATE_LIMITED = 'AUTH_RATE_LIMITED';

    public const VALIDATION_FAILED = 'VALIDATION_FAILED';

    public const CSRF_TOKEN_MISMATCH = 'CSRF_TOKEN_MISMATCH';

    public const ORGANIZER_REQUEST_NOT_FOUND = 'ORGANIZER_REQUEST_NOT_FOUND';

    public const ADMIN_REQUIRED = 'ADMIN_REQUIRED';

    public const ORGANIZER_REQUEST_ROLE_UNAVAILABLE = 'ORGANIZER_REQUEST_ROLE_UNAVAILABLE';

    public const ORGANIZER_REQUEST_CONFLICT = 'ORGANIZER_REQUEST_CONFLICT';

    public const ORGANIZER_REQUEST_ACCOUNT_CONFLICT = 'ORGANIZER_REQUEST_ACCOUNT_CONFLICT';

    public const ORGANIZER_REQUEST_ALREADY_PROCESSED = 'ORGANIZER_REQUEST_ALREADY_PROCESSED';

    public const ORGANIZER_REQUEST_INVALID_ROLE = 'ORGANIZER_REQUEST_INVALID_ROLE';

    public const ORGANIZER_REQUEST_INCOMPLETE = 'ORGANIZER_REQUEST_INCOMPLETE';

    public const ORGANIZER_REQUEST_FAILED = 'ORGANIZER_REQUEST_FAILED';

    public const ORGANIZER_REQUEST_RATE_LIMITED = 'ORGANIZER_REQUEST_RATE_LIMITED';

    public const TOURNAMENT_FORBIDDEN = 'TOURNAMENT_FORBIDDEN';

    public const TOURNAMENT_CREATION_FAILED = 'TOURNAMENT_CREATION_FAILED';

    public const TOURNAMENT_ALREADY_REGISTERED = 'TOURNAMENT_ALREADY_REGISTERED';

    public const TOURNAMENT_REGISTRATION_CLOSED = 'TOURNAMENT_REGISTRATION_CLOSED';

    public const TOURNAMENT_FULL = 'TOURNAMENT_FULL';

    public const TOURNAMENT_REGISTRATION_UNAVAILABLE = 'TOURNAMENT_REGISTRATION_UNAVAILABLE';

    public const TOURNAMENT_REGISTRATION_FAILED = 'TOURNAMENT_REGISTRATION_FAILED';

    /** @var array<string, string> Codes stables ; les messages peuvent évoluer. */
    private const MESSAGES = [
        self::AUTH_REQUIRED => 'Connecte-toi pour accéder à cette page.',
        self::AUTH_INVALID_CREDENTIALS => 'Adresse e-mail ou mot de passe incorrect.',
        self::AUTH_INACTIVE => 'Ton compte est désactivé.',
        self::AUTH_ALREADY_AUTHENTICATED => 'Tu es déjà connecté.',
        self::AUTH_ROLE_UNAVAILABLE => 'La création de compte est temporairement indisponible. Réessaie plus tard.',
        self::AUTH_REGISTRATION_FAILED => 'Impossible de créer le compte pour le moment. Réessaie plus tard.',
        self::AUTH_ACCOUNT_CONFLICT => 'Un compte utilise déjà cette adresse e-mail ou ce pseudo.',
        self::AUTH_RATE_LIMITED => 'Trop de tentatives de connexion. Réessaie dans une minute.',
        self::VALIDATION_FAILED => 'Certains champs sont invalides.',
        self::CSRF_TOKEN_MISMATCH => 'La session a expiré. Recharge le formulaire puis réessaie.',
        self::ORGANIZER_REQUEST_NOT_FOUND => 'Demande introuvable.',
        self::ADMIN_REQUIRED => 'Cette action est réservée aux administrateurs.',
        self::ORGANIZER_REQUEST_ROLE_UNAVAILABLE => 'Les demandes organisateur sont temporairement indisponibles.',
        self::ORGANIZER_REQUEST_CONFLICT => 'Une demande en attente utilise déjà cette adresse e-mail ou ce pseudo.',
        self::ORGANIZER_REQUEST_ACCOUNT_CONFLICT => 'Un compte utilise déjà cette identité. Aucun compte existant n’a été modifié.',
        self::ORGANIZER_REQUEST_ALREADY_PROCESSED => 'Cette demande a déjà été traitée.',
        self::ORGANIZER_REQUEST_INVALID_ROLE => 'Cette demande ne concerne pas le rôle organisateur.',
        self::ORGANIZER_REQUEST_INCOMPLETE => 'Les informations de cette demande ne permettent pas de créer un compte valide.',
        self::ORGANIZER_REQUEST_FAILED => 'La demande n’a pas pu être enregistrée ou traitée. Réessaie plus tard.',
        self::ORGANIZER_REQUEST_RATE_LIMITED => 'Trop de demandes. Réessaie dans une minute.',
        self::TOURNAMENT_FORBIDDEN => 'Seuls les organisateurs peuvent créer un tournoi.',
        self::TOURNAMENT_CREATION_FAILED => 'Le tournoi n’a pas pu être enregistré. Réessaie plus tard.',
        self::TOURNAMENT_ALREADY_REGISTERED => 'Tu es déjà inscrit à ce tournoi.',
        self::TOURNAMENT_REGISTRATION_CLOSED => 'La date limite d’inscription est dépassée.',
        self::TOURNAMENT_FULL => 'Désolé, ce tournoi est complet.',
        self::TOURNAMENT_REGISTRATION_UNAVAILABLE => 'Les inscriptions sont temporairement indisponibles. Réessaie plus tard.',
        self::TOURNAMENT_REGISTRATION_FAILED => 'L’inscription n’a pas pu être enregistrée. Réessaie plus tard.',
        'FIELD_REQUIRED' => 'Le champ :attribute est obligatoire.',
        'FIELD_STRING' => 'Le champ :attribute doit être une chaîne de caractères.',
        'FIELD_EMAIL' => 'L’adresse e-mail doit être valide.',
        'FIELD_MAX' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'FIELD_MIN' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'FIELD_DATE' => 'Le champ :attribute doit être une date valide.',
        'FIELD_INTEGER' => 'Le champ :attribute doit être un nombre entier.',
        'FIELD_MIN_NUMBER' => 'Le champ :attribute doit être supérieur ou égal à :min.',
        'FIELD_MAX_NUMBER' => 'Le champ :attribute ne doit pas dépasser :max.',
        'TOURNAMENT_END_AFTER_START' => 'La date de fin doit être postérieure à la date de début.',
        'TOURNAMENT_DEADLINE_BEFORE_START' => 'La date limite d’inscription ne doit pas dépasser la date de début.',
        'TOURNAMENT_INVALID_CATEGORY' => 'Choisis une discipline valide, pas une catégorie racine ou intermédiaire.',
        'TOURNAMENT_INVALID_STATUS' => 'Choisis un statut de tournoi valide.',
        'BIRTH_DATE_PAST' => 'La date de naissance doit être dans le passé.',
        'PASSWORD_CONFIRMATION' => 'Les deux mots de passe ne correspondent pas.',
        'PASSWORD_TOO_LONG' => 'Le mot de passe ne doit pas dépasser 72 octets avec bcrypt.',
        'FIELD_PROHIBITED' => 'Le champ :attribute ne doit pas être fourni.',
        'ORGANIZER_INVALID_STATUS' => 'Le statut doit être EN_ATTENTE, APPROUVEE ou REFUSEE.',
        'EMAIL_TAKEN' => 'Cette adresse e-mail possède déjà un compte.',
        'USERNAME_TAKEN' => 'Ce pseudo est déjà utilisé.',
        'UNKNOWN_ERROR' => 'Une erreur est survenue. Réessaie plus tard.',
    ];

    /** Retourne le message d’un code, avec un repli pour les codes inconnus. */
    public static function message(string $code): string
    {
        return self::MESSAGES[$code] ?? self::MESSAGES['UNKNOWN_ERROR'];
    }

    /** @return array<string, string> Catalogue complet, sans accès à la base. */
    public static function all(): array
    {
        return self::MESSAGES;
    }

    /**
     * Adapte une erreur au client : JSON avec statut HTTP, ou redirection Blade.
     * Les champs conservés doivent être explicitement sélectionnés par l’appelant.
     *
     * @param  array<string, array<string>>  $errors  Erreurs classées par champ.
     * @param  array<string, mixed>  $oldInput  Champs non sensibles à réafficher.
     * @param  array<string, mixed>  $routeParameters  Paramètres d’une route de détail.
     */
    public static function respond(
        Request $request,
        string $code,
        int $status,
        string $redirectRoute = 'login',
        array $errors = [],
        array $oldInput = [],
        array $routeParameters = [],
    ): JsonResponse|RedirectResponse {
        $message = self::message($code);

        if ($request->expectsJson()) {
            $payload = ['code' => $code, 'message' => $message];

            if ($errors !== []) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        }

        $response = redirect()->route($redirectRoute, $routeParameters)->with('error_code', $code);

        // Ne pas afficher deux fois la même erreur dans les formulaires Blade.
        if ($errors !== []) {
            $response->withErrors($errors);
        } else {
            $response->with('error', $message);
        }

        if ($oldInput !== []) {
            $response->withInput($oldInput);
        }

        return $response;
    }
}
