<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
	use SoftDeletes;

	protected $table = 'users';

	protected $fillable = ['username', 'name', 'hands', 'occupation', 'times_captured', 'posts', 'profile'];

	public function posts()
	{
		return $this->hasMany('Fuzz\MagicBox\Tests\Models\Post');
	}

	public function profile()
	{
		return $this->hasOne('Fuzz\MagicBox\Tests\Models\Profile');
	}

	/**
	 * For unit testing purposes
	 *
	 * @return array
	 */
	public function getFillable()
	{
		return $this->fillable;
	}

	/**
	 * For unit testing purposes
	 *
	 * @param array $fillable
	 * @return $this
	 */
	public function setFillable(array $fillable)
	{
		$this->fillable = $fillable;

		return $this;
	}
}
