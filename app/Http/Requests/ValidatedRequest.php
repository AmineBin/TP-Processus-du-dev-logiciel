<?php

namespace App\Http\Requests;

use App\Support\ErrorHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Contrat de validation partagé : messages publics et anciennes saisies explicitement autorisées. */
abstract class ValidatedRequest extends FormRequest
{
    /** Les middlewares contrôlent l’accès avant la résolution de la Request. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> Messages communs, sans dépendance au domaine auth. */
    public function messages(): array
    {
        return [
            'required' => ErrorHandler::message('FIELD_REQUIRED'),
            'string' => ErrorHandler::message('FIELD_STRING'),
            'email' => ErrorHandler::message('FIELD_EMAIL'),
            'max' => ErrorHandler::message('FIELD_MAX'),
            'min' => ErrorHandler::message('FIELD_MIN'),
            'date' => ErrorHandler::message('FIELD_DATE'),
            'integer' => ErrorHandler::message('FIELD_INTEGER'),
        ];
    }

    /** @return list<string> Liste blanche excluant mots de passe et valeurs sensibles. */
    abstract protected function oldInputFields(): array;

    /** Interrompt le traitement avant le contrôleur avec le contrat d’erreur commun. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ErrorHandler::respond(
            $this,
            ErrorHandler::VALIDATION_FAILED,
            422,
            $this->redirectRoute,
            $validator->errors()->messages(),
            $this->only($this->oldInputFields()),
        ));
    }
}
