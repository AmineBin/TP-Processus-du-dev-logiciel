<?php

namespace App\Http\Requests;

use App\Support\ErrorHandler;

/** Validation commune aux formulaires publics d’authentification. */
abstract class AuthRequest extends ValidatedRequest
{
    /** @return array<string, string> Messages français issus du catalogue partagé. */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'birth_date.before' => ErrorHandler::message('BIRTH_DATE_PAST'),
            'password.confirmed' => ErrorHandler::message('PASSWORD_CONFIRMATION'),
            'email.unique' => ErrorHandler::message('EMAIL_TAKEN'),
            'username.unique' => ErrorHandler::message('USERNAME_TAKEN'),
        ];
    }

    /** @return array<string, string> Libellés affichés à la place des noms techniques. */
    public function attributes(): array
    {
        return [
            'username' => 'pseudo',
            'last_name' => 'nom',
            'first_name' => 'prénom',
            'email' => 'adresse e-mail',
            'phone' => 'numéro de téléphone',
            'birth_date' => 'date de naissance',
            'password' => 'mot de passe',
        ];
    }

    /** @return list<string> Les mots de passe ne sont jamais conservés en session. */
    protected function oldInputFields(): array
    {
        return ['username', 'last_name', 'first_name', 'email', 'phone', 'birth_date'];
    }
}
