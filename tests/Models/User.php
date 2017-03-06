<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\MagicBox\Contracts\MagicBoxResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements MagicBoxResource
{
	use SoftDeletes;

	/**
	 * @const array
	 */
	const FILLABLE = [
		'username',
		'name',
		'hands',
		'occupation',
		'times_captured',
		'posts',
		'profile',
	];

	/**
	 * @const array
	 */
	const INCLUDABLE = [
		'posts',
		'posts.user',
		'profile',
	];

	/**
	 * @var string
	 */
	protected $table = 'users';

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
	 *
	 * @return $this
	 */
	public function setFillable(array $fillable)
	{
		$this->fillable = $fillable;

		return $this;
	}

	/**
	 * Get the list of fields fillable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryFillable(): array
	{
		return self::FILLABLE;
	}

	/**
	 * Get the list of relationships fillable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryIncludable(): array
	{
		return self::INCLUDABLE;
	}
}
