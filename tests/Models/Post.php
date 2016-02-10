<?php

namespace Fuzz\MagicBox\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
	protected $table = 'posts';

	public function user()
	{
		return $this->belongsTo('Fuzz\MagicBox\Tests\Models\User');
	}

	public function tags()
	{
		return $this->belongsToMany('Fuzz\MagicBox\Tests\Models\Tag')->withPivot('extra');
	}
}
