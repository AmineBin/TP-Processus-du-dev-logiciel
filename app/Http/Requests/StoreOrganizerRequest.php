<?php

namespace App\Http\Requests;

use App\Rules\AvailableOrganizerIdentity;
use App\Support\ErrorHandler;
use Closure;
use Illuminate\Support\Facades\Hash;

/** Données du formulaire organisateur, sans aucun champ de rôle ou de traitement client. */
class StoreOrganizerRequest extends AuthRequest
{
    protected $redirectRoute = 'register';

    /** Uniformise l’adresse sans modifier le mot de passe ni l’orthographe du pseudo. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, array<mixed>> Identité complète requise pour créer un compte après validation. */
    public function rules(): array
    {
        return [
            'username' => ['bail', 'required', 'string', 'max:50', new AvailableOrganizerIdentity('nom_utilisateur')],
            'last_name' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'email' => ['bail', 'required', 'string', 'email', 'max:100', new AvailableOrganizerIdentity('email')],
            'phone' => ['required', 'string', 'max:10'],
            'birth_date' => ['required', 'date', 'before:today'],
            'password' => [
                'bail', 'required', 'string', 'min:8', 'confirmed',
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Bcrypt limite la taille en octets, pas en caractères Unicode.
                    if (Hash::getDefaultDriver() === 'bcrypt' && strlen($value) > 72) {
                        $fail(ErrorHandler::message('PASSWORD_TOO_LONG'));
                    }
                },
            ],
            'role_id' => ['prohibited'],
            'role' => ['prohibited'],
            'statut' => ['prohibited'],
            'status' => ['prohibited'],
            'traite_par' => ['prohibited'],
            'date_traitement' => ['prohibited'],
            'utilisateur_cree_id' => ['prohibited'],
            'mot_de_passe_hash' => ['prohibited'],
        ];
    }

    /** @return array<string, string> Les tentatives d’attribution de rôle échouent explicitement. */
    public function messages(): array
    {
        return [...parent::messages(), 'prohibited' => ErrorHandler::message('FIELD_PROHIBITED')];
    }
}
