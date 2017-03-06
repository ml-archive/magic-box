<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\MagicBox\Contracts\MagicBoxResource;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model implements MagicBoxResource
{
	/**
	 * @const array
	 */
	const FILLABLE = [
		'user_id',
		'favorite_cheese',
		'favorite_fruit',
		'is_human',
		'user',
	];

	/**
	 * @const array
	 */
	const INCLUDABLE = ['user',];

	/**
	 * @var string
	 */
	protected $table = 'profiles';

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('Fuzz\MagicBox\Tests\Models\User');
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
