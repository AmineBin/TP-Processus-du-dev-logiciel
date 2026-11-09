<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Préinscription organisateur : aucun compte utilisateur n’existe avant l’approbation. */
class OrganizerRequest extends Model
{
    public const PENDING = 'EN_ATTENTE';

    public const APPROVED = 'APPROUVEE';

    public const REJECTED = 'REFUSEE';

    public const STATUSES = [self::PENDING, self::APPROVED, self::REJECTED];

    protected $table = 'demande_inscription';

    public $timestamps = false;

    protected $fillable = [
        'nom_utilisateur', 'email', 'mot_de_passe_hash',
        'prenom', 'nom', 'date_naissance', 'numero_telephone',
    ];

    // Même une sérialisation accidentelle du modèle ne doit pas exposer les identifiants secrets.
    protected $hidden = ['mot_de_passe_hash', 'email_en_attente', 'pseudo_en_attente'];

    /** @return array<string, string> Dates métier et IDs de référence. */
    protected function casts(): array
    {
        return [
            'date_creation' => 'datetime',
            'date_traitement' => 'datetime',
            'date_naissance' => 'date',
            'role_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Role, $this> Rôle demandé, fixé à ORGANISATEUR côté serveur. */
    public function requestedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** @return BelongsTo<User, $this> Administrateur ayant traité la demande. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    /** @return BelongsTo<User, $this> Compte effectivement créé lors de l’approbation. */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_cree_id');
    }

    /**
     * @param  Builder<OrganizerRequest>  $query
     * @return Builder<OrganizerRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('statut', self::PENDING);
    }

    /**
     * @param  Builder<OrganizerRequest>  $query
     * @return Builder<OrganizerRequest>
     */
    public function scopeForOrganizers(Builder $query): Builder
    {
        return $query->whereHas('requestedRole', fn (Builder $role) => $role->where('code', Role::ORGANIZER_CODE));
    }
}
