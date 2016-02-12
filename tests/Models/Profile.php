<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
	/**
	 * @var string
	 */
	protected $table = 'profiles';

	/**
	 * @var array
	 */
	protected $fillable = [
		'user_id',
		'favorite_cheese',
		'favorite_fruit',
		'is_human',
		'user',
	];

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
}
