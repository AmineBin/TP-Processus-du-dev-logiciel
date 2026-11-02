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
        'FIELD_REQUIRED' => 'Le champ :attribute est obligatoire.',
        'FIELD_STRING' => 'Le champ :attribute doit être une chaîne de caractères.',
        'FIELD_EMAIL' => 'L’adresse e-mail doit être valide.',
        'FIELD_MAX' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'FIELD_MIN' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'FIELD_DATE' => 'Le champ :attribute doit être une date valide.',
        'BIRTH_DATE_PAST' => 'La date de naissance doit être dans le passé.',
        'PASSWORD_CONFIRMATION' => 'Les deux mots de passe ne correspondent pas.',
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
     */
    public static function respond(
        Request $request,
        string $code,
        int $status,
        string $redirectRoute = 'login',
        array $errors = [],
        array $oldInput = [],
    ): JsonResponse|RedirectResponse {
        $message = self::message($code);

        if ($request->expectsJson()) {
            $payload = ['code' => $code, 'message' => $message];

            if ($errors !== []) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        }

        $response = redirect()->route($redirectRoute)->with('error_code', $code);

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
