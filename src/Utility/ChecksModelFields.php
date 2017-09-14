<?php

namespace Fuzz\MagicBox\Utility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChecksModelFields
 *
 * Finds fields for a model.
 *
 * @package Fuzz\MagicBox\Utility
 */
trait ChecksModelFields
{
	/**
	 * Return a model's fields.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @return array
	 */
	public static function getFields(Model $instance): array
	{
		return Schema::getColumnListing($instance->getTable());
	}
}
