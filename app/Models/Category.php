<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categorie';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'categorie_parent_id',
        'description',
    ];

    public function tournois(): HasMany
    {
        return $this->hasMany(Tournament::class, 'categorie_id');
    }
}