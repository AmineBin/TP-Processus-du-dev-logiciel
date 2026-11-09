<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Support\ErrorHandler;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/** Contrat de création d’un tournoi, cohérent avec les écrans de consultation et d’inscription. */
class StoreTournamentRequest extends ValidatedRequest
{
    protected $redirectRoute = 'tournaments.create';

    /** @return array<string, array<mixed>> Aucun organisateur fourni par le client n’est accepté. */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'regles' => ['nullable', 'string', 'max:5000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'date_limite_inscription' => ['required', 'date', 'before_or_equal:date_debut'],
            'nombre_max_participants' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'categorie_id' => [
                'required',
                'integer',
                // Même périmètre que les options du formulaire : pas de catégorie racine.
                Rule::exists('categorie', 'id')->where(
                    fn (Builder $query) => $query->whereIn('id', Category::disciplines()->select('categorie.id')),
                ),
            ],
            'statut_id' => ['required', 'integer', 'exists:statut_tournoi,id'],
        ];
    }

    /** @return array<string, string> Messages métier issus du catalogue partagé. */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'date_fin.after' => ErrorHandler::message('TOURNAMENT_END_AFTER_START'),
            'date_limite_inscription.before_or_equal' => ErrorHandler::message('TOURNAMENT_DEADLINE_BEFORE_START'),
            'nombre_max_participants.min' => ErrorHandler::message('FIELD_MIN_NUMBER'),
            'nombre_max_participants.max' => ErrorHandler::message('FIELD_MAX_NUMBER'),
            'categorie_id.exists' => ErrorHandler::message('TOURNAMENT_INVALID_CATEGORY'),
            'statut_id.exists' => ErrorHandler::message('TOURNAMENT_INVALID_STATUS'),
        ];
    }

    /** @return array<string, string> Libellés lisibles pour les erreurs de champs. */
    public function attributes(): array
    {
        return [
            'nom' => 'nom du tournoi',
            'description' => 'description',
            'regles' => 'règles',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'date_limite_inscription' => 'date limite d’inscription',
            'nombre_max_participants' => 'nombre maximum de participants',
            'categorie_id' => 'discipline',
            'statut_id' => 'statut',
        ];
    }

    /** @return list<string> Restaure le formulaire sans conserver d’attributs injectés. */
    protected function oldInputFields(): array
    {
        return [
            'nom', 'description', 'regles', 'date_debut', 'date_fin',
            'date_limite_inscription', 'nombre_max_participants', 'categorie_id', 'statut_id',
        ];
    }
}
