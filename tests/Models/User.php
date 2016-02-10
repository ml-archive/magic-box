<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	protected $table = 'users';

	public function posts()
	{
		return $this->hasMany('Fuzz\MagicBox\Tests\Models\Post');
	}

	public function profile()
	{
		return $this->hasOne('Fuzz\MagicBox\Tests\Models\Profile');
	}
}
