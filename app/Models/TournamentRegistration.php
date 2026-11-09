<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Participation unique d’un utilisateur à un tournoi, avec son statut réel. */
class TournamentRegistration extends Model
{
    protected $table = 'inscription';

    public $timestamps = false;

    /** @return array<string, string> Date de la dernière inscription ou réinscription. */
    protected function casts(): array
    {
        return ['date_inscription' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> Participant imposé par la session. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /** @return BelongsTo<Tournament, $this> Tournoi concerné. */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournoi_id');
    }

    /** @return BelongsTo<RegistrationStatus, $this> Statut de référence, chargé pour l’affichage. */
    public function status(): BelongsTo
    {
        return $this->belongsTo(RegistrationStatus::class, 'statut_id');
    }

    /** Une annulation ne réserve aucune place et n’est pas une confirmation. */
    public function isActive(): bool
    {
        return in_array($this->status?->code, RegistrationStatus::ACTIVE_CODES, true);
    }

    /** Une ligne annulée peut être réutilisée, sans violer la contrainte d’unicité. */
    public function isCancelled(): bool
    {
        return $this->status?->code === RegistrationStatus::CANCELLED;
    }

    /**
     * Places réservées : inscriptions en attente et confirmées seulement.
     *
     * @param  Builder<TournamentRegistration>  $query
     * @return Builder<TournamentRegistration>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $status) => $status->whereIn('code', RegistrationStatus::ACTIVE_CODES));
    }
}
