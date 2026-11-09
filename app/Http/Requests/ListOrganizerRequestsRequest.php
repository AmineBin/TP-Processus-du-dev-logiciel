<?php

namespace App\Http\Requests;

use App\Models\OrganizerRequest;
use App\Support\ErrorHandler;
use Illuminate\Validation\Rule;

/** Filtres explicites et pagination bornée de la liste administrative. */
class ListOrganizerRequestsRequest extends ValidatedRequest
{
    protected $redirectRoute = 'home';

    /** @return array<string, array<mixed>> Aucun statut arbitraire ni pagination illimitée. */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', Rule::in(OrganizerRequest::STATUSES)],
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:2147483647'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array<string, string> Messages adaptés aux nombres et au filtre. */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'min' => ErrorHandler::message('FIELD_MIN_NUMBER'),
            'max' => ErrorHandler::message('FIELD_MAX_NUMBER'),
            'in' => ErrorHandler::message('ORGANIZER_INVALID_STATUS'),
        ];
    }

    /** @return list<string> Aucun formulaire HTML ni donnée personnelle à conserver. */
    protected function oldInputFields(): array
    {
        return [];
    }
}
