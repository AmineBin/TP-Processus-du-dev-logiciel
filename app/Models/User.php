<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
	}
}

