<?php

namespace App\Http\Requests;

/** Contrat d’entrée de POST /login ; aucun accès métier à la base ici. */
class LoginRequest extends AuthRequest
{
    protected $redirectRoute = 'login';

    /** @return array<string, array<string>> Champs nécessaires à l’authentification. */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:100'],
            'password' => ['required', 'string'],
        ];
    }
}
