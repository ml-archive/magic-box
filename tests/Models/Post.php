<?php

namespace Fuzz\MagicBox\Tests\Models;

use Fuzz\Data\Eloquent\Model;

class Post extends Model
{
	public function user()
	{
		return $this->belongsTo('Fuzz\MagicBox\Tests\Models\User');
	}

	public function tags()
	{
		return $this->belongsToMany('Fuzz\MagicBox\Tests\Models\Tag')->withPivot('extra');
	}
}
