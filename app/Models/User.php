<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
	protected $table = 'utilisateur';

	public $timestamps = false;

	protected $fillable = [
		'nom_utilisateur',
		'mot_de_passe_hash',
		'date_naissance',
		'email',
		'numero_telephone',
		'prenom',
		'nom',
		'role_id',
	];

	public function getAuthPassword(): string
	{
		return $this->mot_de_passe_hash;
	}

	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
	}
}
