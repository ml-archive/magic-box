<?php

namespace Fuzz\MagicBox;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Filter
{
	/**
	 * Supported filter methods
	 *
	 * @var array
	 */
	protected static $supported_tokens = [
		'^'  => 'startsWith',
		'~'  => 'contains',
		'$'  => 'endsWith',
		'<'  => 'lessThan',
		'>'  => 'greaterThan',
		'>=' => 'greaterThanOrEquals',
		'<=' => 'lessThanOrEquals',
		'='  => 'equals',
		'!=' => 'notEquals',
		'![' => 'notIn',
		'['  => 'in',
		'!'  => 'doesntHave'
	];

	/**
	 * Tokens that accept non-scalar filters.
	 * ex: [One,Two,Three,Fuzz]
	 *
	 * @var array
	 */
	protected static $non_scalar_tokens = [
		'![',
		'['
	];

	/**
	 * Determine the token (if any) to use for the query
	 *
	 * @param string $filter
	 * @return bool|string
	 */
	private static function determineTokenType($filter)
	{
		if (in_array(substr($filter, 0, 2), array_keys(self::$supported_tokens))) {
			// Two character token (<=, >=, etc)
			return substr($filter, 0, 2);
		} elseif (in_array($filter[0], array_keys(self::$supported_tokens))) {
			// Single character token (>, ^, $)
			return $filter[0];
		}

		// No token
		return false;
	}

	/**
	 * Determine if a token should accept a scalar value
	 *
	 * @param string $token
	 * @return bool
	 */
	private static function shouldBeScalar($token)
	{
		// Is token in array of tokens that can be non-scalar
		return ! in_array($token, self::$non_scalar_tokens);
	}

	/**
	 * Parse filter to accept some filter utility keywords
	 *
	 * @param string $filter
	 * @return mixed
	 */
	private static function parseFilterKeyWords($filter)
	{
		// Include here as we can't include expressions in default field values
		$keywords = [
			'NOW()' => function () {
				return Carbon::now();
			}
		];

		Log::info(Carbon::now());

		return array_key_exists($filter, $keywords) ? $keywords[$filter]() : $filter;
	}

	/**
	 * Parse a filter string and confirm that it has a scalar value if it should.
	 *
	 * @param string $token
	 * @param string $filter
	 * @return array|bool
	 */
	private static function cleanAndValidateFilter($token, $filter)
	{
		$filter_should_be_scalar = self::shouldBeScalar($token);

		// Format the filter, cutting off the trailing ']' if appropriate
		$filter = $filter_should_be_scalar ? explode(',', substr($filter, strlen($token))) :
			explode(',', substr($filter, strlen($token), -1));

		if ($filter_should_be_scalar) {
			if (count($filter) > 1) {
				return false;
			}

			// Set to first index if should be scalar
			$filter = self::parseFilterKeyWords($filter[0]);
		}

		return $filter;
	}

	/**
	 * Funnel method to filter queries.
	 *
	 * First check for a dot nested string in the place of a filter column and use the appropriate method
	 * and relation combination.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array                                 $filters
	 * @return void
	 */
	public static function filterQuery($query, $filters)
	{
		foreach ($filters as $column => $filter) {
			$nested_relations = self::parseRelations($column);

			if (is_array($nested_relations)) {
				// Create a dot nested string of relations
				$relation = implode('.', array_splice($nested_relations, 0, count($nested_relations) - 1));
				// Set up the column at the end of the dot nested relation
				$column = end($nested_relations);
			}

			if ($token = self::determineTokenType($filter)) {
				if (! $filter = self::cleanAndValidateFilter($token, $filter)) {
					continue;
				}

				$method = self::$supported_tokens[$token];

				// Querying a dot nested relation
				if (is_array($nested_relations)) {
					$query->whereHas(
						$relation, function ($query) use ($method, $column, $filter) {
						self::$method($column, $filter, $query);
					}
					);
				} else {
					self::$method($column, $filter, $query);
				}
			} elseif ($filter === 'true' || $filter === 'false') {
				// Is a boolean filter, coerce to boolean.
				$filter = ($filter === 'true');
				$where  = camel_case('where' . $column);

				// Querying a dot nested relation
				if (is_array($nested_relations)) {
					$query->whereHas(
						$relation, function ($query) use ($where, $filter) {
						$query->$where($filter);
					}
					);
				} else {
					$query->$where($filter);
				}
			} elseif ($filter === 'NULL' || $filter === 'NOT_NULL') {
				// Querying a dot nested relation
				if (is_array($nested_relations)) {
					$query->whereHas(
						$relation, function ($query) use ($column, $filter) {
						self::nullMethod($column, $filter, $query);
					}
					);
				} else {
					self::nullMethod($column, $filter, $query);
				}
			} else {
				// @todo Unsupported type
			}
		}
	}

	/**
	 * Parse a string of dot nested relations, if applicable
	 *
	 * Ex: users?filters[posts.comments.rating]=>4
	 *
	 * @param string $filter_name
	 * @return array
	 */
	protected static function parseRelations($filter_name)
	{
		// Determine if we're querying a dot nested relationships of arbitrary depth (ex: user.post.tags.label)
		$parse_relations = explode('.', $filter_name);

		return count($parse_relations) === 1 ? $parse_relations[0] : $parse_relations;
	}

	/**
	 * Query for items that begin with a string.
	 *
	 * Ex: users?filters[name]=^John
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function startsWith($column, $filter, $query)
	{
		$query->where($column, 'LIKE', $filter . '%');
	}

	/**
	 * Query for items that end with a string.
	 *
	 * Ex: users?filters[name]=$Smith
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function endsWith($column, $filter, $query)
	{
		$query->where($column, 'LIKE', '%' . $filter);
	}

	/**
	 * Query for items that contain a string.
	 *
	 * Ex: users?filters[favorite_cheese]=~cheddar
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function contains($column, $filter, $query)
	{
		$query->where($column, 'LIKE', '%' . $filter . '%');
	}

	/**
	 * Query for items with a value less than a filter.
	 *
	 * Ex: users?filters[lifetime_value]=<50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function lessThan($column, $filter, $query)
	{
		Log::info('less than ' . print_r($filter, true));
		$query->where($column, '<', $filter);
	}

	/**
	 * Query for items with a value greater than a filter.
	 *
	 * Ex: users?filters[lifetime_value]=>50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function greaterThan($column, $filter, $query)
	{
		$query->where($column, '>', $filter);
	}

	/**
	 * Query for items with a value greater than or equal to a filter.
	 *
	 * Ex: users?filters[lifetime_value]=>=50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function greaterThanOrEquals($column, $filter, $query)
	{
		$query->where($column, '>=', $filter);
	}

	/**
	 * Query for items with a value less than or equal to a filter.
	 *
	 * Ex: users?filters[lifetime_value]=<=50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function lessThanOrEquals($column, $filter, $query)
	{
		$query->where($column, '<=', $filter);
	}

	/**
	 * Query for items with a value equal to a filter.
	 *
	 * Ex: users?filters[username]==Specific%20Username
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function equals($column, $filter, $query)
	{
		$where = camel_case('where' . $column);
		$query->$where($filter);
	}

	/**
	 * Query for items with a value not equal to a filter.
	 *
	 * Ex: users?filters[username]=!=common%20username
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function notEquals($column, $filter, $query)
	{
		$query->where($column, '!=', $filter);
	}

	/**
	 * Query for items that are either null or not null.
	 *
	 * Ex: users?filters[email]=NOT_NULL
	 * Ex: users?filters[address]=NULL
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function nullMethod($column, $filter, $query)
	{
		if ($filter === 'NULL') {
			$query->whereNull($column);
		} else {
			$query->whereNotNull($column);
		}
	}

	/**
	 * Query for items that are in a list.
	 *
	 * Ex: users?filters[id]=[1,5,10]
	 *
	 * @param string                                $column
	 * @param string|array                          $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function in($column, $filter, $query)
	{
		$query->whereIn($column, $filter);
	}

	/**
	 * Query for items that are not in a list.
	 *
	 * Ex: users?filters[id]=![1,5,10]
	 *
	 * @param string                                $column
	 * @param string|array                          $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function notIn($column, $filter, $query)
	{
		$query->whereNotIn($column, $filter);
	}

	/**
	 * Query for items that don't have any of a relationship
	 *
	 * @param string|null                           $column
	 * @param string|array                          $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 */
	protected static function doesntHave($column = null, $filter, $query)
	{
		$query->whereDoesntHave($filter);
	}
}
