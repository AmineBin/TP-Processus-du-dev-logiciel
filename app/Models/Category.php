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

    /** @return HasMany<Tournament, $this> Tournois directement rattachés à la catégorie. */
    public function tournois(): HasMany
    {
        return $this->hasMany(Tournament::class, 'categorie_id');
    }

    /**
     * IDs du nœud et de tous ses descendants, sans boucle en cas de données cycliques.
     *
     * @return list<int>
     */
    public function subtreeIds(): array
    {
        $children = self::query()->get(['id', 'categorie_parent_id'])->groupBy('categorie_parent_id');
        $pending = [(int) $this->getKey()];
        $ids = [];

        while ($pending !== []) {
            $id = array_pop($pending);
            if (isset($ids[$id])) {
                continue;
            }
            $ids[$id] = $id;
            foreach ($children->get($id, collect()) as $child) {
                $pending[] = (int) $child->id;
            }
        }

        return array_values($ids);
    }

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
