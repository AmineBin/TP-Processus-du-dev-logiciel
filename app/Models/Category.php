<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catégorie existante ; les tournois créés sont rattachés à une discipline feuille. */
class Category extends Model
{
    protected $table = 'categorie';

    public $timestamps = false;

    /** @return HasMany<Category, $this> Sous-catégories de ce nœud. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'categorie_parent_id');
    }

    /** @return BelongsTo<Category, $this> Catégorie parente pour contextualiser le formulaire. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'categorie_parent_id');
    }

    /**
     * Exclut les racines et nœuds intermédiaires, absents des listes de disciplines.
     *
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeDisciplines(Builder $query): Builder
    {
        return $query->whereNotNull('categorie_parent_id')->doesntHave('children');
    }
}
