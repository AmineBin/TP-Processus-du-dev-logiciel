<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Référentiel des inscriptions : les codes, et non les IDs, définissent leur état. */
class RegistrationStatus extends Model
{
    public const PENDING = 'EN_ATTENTE';

    public const CONFIRMED = 'CONFIRMEE';

    public const CANCELLED = 'ANNULEE';

    public const ACTIVE_CODES = [self::PENDING, self::CONFIRMED];

    protected $table = 'statut_inscription';

    public $timestamps = false;
}
