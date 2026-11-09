<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tournoi créé à partir des seules données validées, avec organisateur imposé côté serveur. */
class Tournament extends Model
{
    protected $table = 'tournoi';

    // Le schéma gère date_creation et date_modification par ses valeurs par défaut.
    public $timestamps = false;

    protected $fillable = [
        'nom',
        'description',
        'regles',
        'date_debut',
        'date_fin',
        'date_limite_inscription',
        'nombre_max_participants',
        'categorie_id',
        'statut_id',
    ];

    /** @return array<string, string> Dates et capacité utilisées par les parcours métier. */
    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'date_limite_inscription' => 'datetime',
            'nombre_max_participants' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> Créateur authentifié, jamais choisi par le formulaire. */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }

    /** @return BelongsTo<Category, $this> Discipline dans laquelle le tournoi sera visible. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_id');
    }
}
