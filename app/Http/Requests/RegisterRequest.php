<?php

namespace App\Http\Requests;

/** Contrat d’entrée de POST /register, aligné sur les contraintes de utilisateur. */
class RegisterRequest extends AuthRequest
{
    protected $redirectRoute = 'register';

    /** @return array<string, array<string>> Le rôle et l’état actif ne viennent jamais du client. */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', 'unique:utilisateur,nom_utilisateur'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:utilisateur,email'],
            'phone' => ['required', 'string', 'max:10'],
            'birth_date' => ['required', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
