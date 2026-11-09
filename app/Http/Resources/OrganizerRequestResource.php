<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Liste blanche administrative : aucun mot de passe, hachage ou champ technique d’unicité. */
class OrganizerRequestResource extends JsonResource
{
    /** @return array<string, mixed> Contrat stable pour le futur panel admin. */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->nom_utilisateur,
            'email' => $this->email,
            'first_name' => $this->prenom,
            'last_name' => $this->nom,
            'phone' => $this->numero_telephone,
            'birth_date' => $this->date_naissance?->toDateString(),
            'requested_role' => $this->requestedRole?->code,
            'status' => $this->statut,
            'submitted_at' => $this->date_creation?->toIso8601String(),
            'reviewed_at' => $this->date_traitement?->toIso8601String(),
            'reviewed_by' => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'username' => $this->reviewer->nom_utilisateur,
            ] : null,
            'rejection_reason' => $this->motif_refus,
            'created_user_id' => $this->utilisateur_cree_id,
        ];
    }
}
