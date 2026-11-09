<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tournament extends Model
{
    protected $table = 'tournoi';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'description',
        'regles',
        'date_debut',
        'date_fin',
        'date_limite_inscription',
        'nombre_max_participants',
        'organisateur_id',
        'categorie_id',
        'statut_id',
    ];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_id');
    }

    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'inscription',
            'tournoi_id',
            'utilisateur_id'
        )->withPivot('id', 'date_inscription', 'statut_id');
    }
}