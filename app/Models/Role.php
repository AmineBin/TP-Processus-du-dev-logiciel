<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Rôle identifié par son code métier, jamais par un ID supposé fixe. */
class Role extends Model
{
    public const USER_CODE = 'UTILISATEUR';

    public const ADMIN_CODE = 'ADMIN';

    public const ORGANIZER_CODE = 'ORGANISATEUR';

    protected $table = 'role_utilisateur';

    public $timestamps = false;

    protected $fillable = ['code', 'libelle', 'description'];

    /** @return HasMany<User, $this> Utilisateurs possédant ce rôle. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
