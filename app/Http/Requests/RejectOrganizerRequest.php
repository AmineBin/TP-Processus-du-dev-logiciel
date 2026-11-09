<?php

namespace App\Http\Requests;

/** Motif obligatoire pour expliquer et tracer un refus administratif. */
class RejectOrganizerRequest extends ValidatedRequest
{
    protected $redirectRoute = 'home';

    /** @return array<string, array<string>> Limite alignée sur motif_refus en BDD. */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:500']];
    }

    /** @return array<string, string> Libellé lisible dans la réponse de validation. */
    public function attributes(): array
    {
        return ['reason' => 'motif du refus'];
    }

    /** @return list<string> Seul le motif est une entrée métier valide. */
    protected function oldInputFields(): array
    {
        return ['reason'];
    }
}
