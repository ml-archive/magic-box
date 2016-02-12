<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
	use SoftDeletes;

	/**
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * @var array
	 */
	protected $fillable = ['username', 'name', 'hands', 'occupation', 'times_captured', 'posts', 'profile'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function posts()
	{
		return $this->hasMany('Fuzz\MagicBox\Tests\Models\Post');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
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
