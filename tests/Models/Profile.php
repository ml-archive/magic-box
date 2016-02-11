<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
	protected $table = 'profiles';

	protected $fillable = ['user_id', 'favorite_cheese', 'favorite_fruit', 'is_human', 'user'];

	public $timestamps = false;

	public function user()
	{
		return $this->belongsTo('Fuzz\MagicBox\Tests\Models\User');
	}
}
