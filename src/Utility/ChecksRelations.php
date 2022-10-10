<?php

namespace Fuzz\MagicBox\Utility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class ChecksRelations
 *
 * Safely determine if a key is a relation on a model.
 *
 * @package Fuzz\MagicBox\Utility
 */
trait ChecksRelations
{
	/**
	 * Safely determine if the specified key name is a relation on the model instance
	 *
	 * @todo use PHP7 return type reflection solution as well
	 *
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @param string                              $key
	 * @param string                              $model_class
	 * @return \Illuminate\Database\Eloquent\Relations\Relation|bool
	 */
	protected function isRelation(Model $instance, $key, $model_class)
	{
		// Not a relation method
		if (! method_exists($instance, $key)) {
			return false;
		}

		$relation      = null;
		$safe_instance = new $model_class;

		// Get method, dirty and imperfect
		$reflected_method = (new \ReflectionMethod($safe_instance, $key))->__toString();

		$supported_relations = [
			BelongsTo::class,
			HasOne::class,
			HasMany::class,
			BelongsToMany::class,
		];

		// Find which, if any, of the supported relations are present in the reflected string
		foreach ($supported_relations as $supported_relation) {
			if (strpos($reflected_method, $supported_relation) !== false) {
				$relation = $instance->$key();
				break;
			}
		}

		// If the ReflectionMethod guess fails, try to guess based on the concrete return type of a safe instance
		// of the model
		if (is_null($relation) && ($safe_instance->$key() instanceof Relation)) {
			// If the method returns a Relation, we can safely call it
			$relation = $instance->$key();
		}

		return is_null($relation) ? false : $relation;
	}
}
