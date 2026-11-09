<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Statuts de référence affichés dans le formulaire, sans supposer leurs IDs. */
class TournamentStatus extends Model
{
    protected $table = 'statut_tournoi';

    public $timestamps = false;
}
