<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
	/**
	 * @var string
	 */
	protected $table = 'posts';

	/**
	 * @var array
	 */
	protected $fillable = ['title', 'user_id', 'user', 'tags'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo('Fuzz\MagicBox\Tests\Models\User');
	}

	/**
	 * @return $this
	 */
	public function tags()
	{
		return $this->belongsToMany('Fuzz\MagicBox\Tests\Models\Tag')->withPivot('extra');
	}
}
