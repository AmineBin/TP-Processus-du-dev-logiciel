<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /** @return BelongsTo<Role, $this> Rôle attribué exclusivement côté serveur. */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tournois(): BelongsToMany
{
    return $this->belongsToMany(
        Tournament::class,
        'inscription',
        'utilisateur_id',
        'tournoi_id'
    )->withPivot('id', 'date_inscription', 'statut_id');
}
}
