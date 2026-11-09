<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** Utilisateur authentifiable, adapté aux noms de colonnes du schéma existant. */
class User extends Authenticatable
{
    protected $table = 'utilisateur';

    // Auth::attempt() lit ce champ, et non une colonne « password » inexistante.
    protected $authPasswordName = 'mot_de_passe_hash';

    // Le schéma ne possède pas de remember_token : pas de connexion persistante.
    protected $rememberTokenName = '';

    protected $hidden = ['mot_de_passe_hash'];

    public $timestamps = false;

    // Le rôle et est_actif sont volontairement exclus de l’assignation de masse.
    protected $fillable = [
        'nom_utilisateur',
        'mot_de_passe_hash',
        'date_naissance',
        'email',
        'numero_telephone',
        'prenom',
        'nom',
    ];

    /** @return array<string, string> Types utilisés lors de la lecture des attributs. */
    protected function casts(): array
    {
        return [
            'est_actif' => 'boolean',
            'date_naissance' => 'date',
            'date_creation' => 'datetime',
        ];
    }

    /** Vérifie le code métier du rôle, sans supposer un ID numérique fixe. */
    public function isOrganizer(): bool
    {
        return $this->role?->code === Role::ORGANIZER_CODE;
    }

    /** @return BelongsTo<Role, $this> Rôle attribué exclusivement côté serveur. */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** @return HasMany<TournamentRegistration, $this> Inscriptions du compte, y compris l’historique annulé. */
    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class, 'utilisateur_id');
    }

    /** @return BelongsToMany<Tournament, $this> Tournois liés au compte avec leur pivot. */
    public function tournois(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'inscription', 'utilisateur_id', 'tournoi_id')
            ->withPivot('id', 'date_inscription', 'statut_id');
    }
}
