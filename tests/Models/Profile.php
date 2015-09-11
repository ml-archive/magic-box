<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\Data\Eloquent\Model;

class Profile extends Model
{
	protected $table = 'profiles';

	public $timestamps = false;

	public function user()
	{
		return $this->belongsTo('Fuzz\MagicBox\Tests\Models\User');
	}
}
