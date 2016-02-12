<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
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
}
