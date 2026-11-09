<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** @return HasMany<TournamentRegistration, $this> Participations et historique d’annulation. */
    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class, 'tournoi_id');
    }

    /** @return BelongsToMany<User, $this> Relation inverse avec les champs du pivot. */
    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inscription', 'tournoi_id', 'utilisateur_id')
            ->withPivot('id', 'date_inscription', 'statut_id');
    }

    /** Une clôture absente sur une ancienne ligne utilise le début comme limite. */
    public function registrationDeadlinePassed(): bool
    {
        return now()->greaterThan($this->date_limite_inscription ?? $this->date_debut);
    }

    /** @return BelongsTo<Category, $this> Alias conservé pour les usages francophones du profil. */
    public function categorie(): BelongsTo
    {
        return $this->category();
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
