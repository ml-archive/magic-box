<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\MagicBox\Contracts\MagicBoxResource;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements MagicBoxResource
{
	/**
	 * @const array
	 */
	const FILLABLE = [
		'title',
		'user_id',
		'user',
		'tags',
	];

	/**
	 * @const array
	 */
	const INCLUDABLE = [
		'user',
		'tags',
	];

	/**
	 * @const array
	 */
	const FILTERABLE = [
		'username',
		'name',
		'hands',
		'occupation',
		'times_captured',
		'posts.label',
	];

	/**
	 * @var string
	 */
	protected $table = 'posts';

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function not_includable()
	{
		return $this->belongsTo(NotIncludable::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function tags()
	{
		return $this->belongsToMany(Tag::class)->withPivot('extra');
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

	/**
	 * Get the list of fields filterable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryFilterable(): array
	{
		return self::FILTERABLE;
	}
}
