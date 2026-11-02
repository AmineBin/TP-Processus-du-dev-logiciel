<?php

namespace App\Http\Requests;

use App\Support\ErrorHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Validation commune aux formulaires publics d’authentification. */
abstract class AuthRequest extends FormRequest
{
    /** L’accès aux formulaires est contrôlé par le middleware guest. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> Messages français issus du catalogue partagé. */
    public function messages(): array
    {
        return [
            'required' => ErrorHandler::message('FIELD_REQUIRED'),
            'string' => ErrorHandler::message('FIELD_STRING'),
            'email' => ErrorHandler::message('FIELD_EMAIL'),
            'max' => ErrorHandler::message('FIELD_MAX'),
            'min' => ErrorHandler::message('FIELD_MIN'),
            'date' => ErrorHandler::message('FIELD_DATE'),
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

    /** Renvoie les erreurs avant d’exécuter le contrôleur, sans conserver les mots de passe. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ErrorHandler::respond(
            $this,
            ErrorHandler::VALIDATION_FAILED,
            422,
            $this->redirectRoute,
            $validator->errors()->messages(),
            $this->only(['username', 'last_name', 'first_name', 'email', 'phone', 'birth_date']),
        ));
    }
}
