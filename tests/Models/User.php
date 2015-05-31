<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\Data\Eloquent\Model;

class User extends Model
{
	public function posts()
	{
		return $this->hasMany('Fuzz\MagicBox\Tests\Models\Post');
	}

	public function profile()
	{
		return $this->hasOne('Fuzz\MagicBox\Tests\Models\Profile');
	}
}
