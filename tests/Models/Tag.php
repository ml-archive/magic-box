<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\MagicBox\Contracts\MagicBoxResource;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model implements MagicBoxResource
{
	/**
	 * @const array
	 */
	const FILLABLE = [
		'label',
		'posts',
	];

	/**
	 * @const array
	 */
	const INCLUDABLE = ['user', 'posts'];

	/**
	 * @var string
	 */
	protected $table = 'tags';

	/**
	 * @var array
	 */
	protected $fillable = [
		'label',
		'posts',
	];

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function posts()
	{
		return $this->belongsToMany('Fuzz\MagicBox\Tests\Models\Post')->withPivot('extra');
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
