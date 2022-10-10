<?php

namespace Fuzz\MagicBox;

use Fuzz\MagicBox\Contracts\QueryFilterContainer;

/**
 * Class Filter
 *
 * A QueryFilterContainer implementation for Eloquent.
 *
 * @package Fuzz\MagicBox
 */
class Filter implements QueryFilterContainer
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
	];

	/**
	 * Tokens that accept non-scalar filters.
	 * ex: [One,Two,Three,Fuzz]
	 *
	 * @var array
	 */
	protected static $non_scalar_tokens = [
		'![',
		'[',
	];

	/**
	 * Container for base table prefix. Always specify table.
	 *
	 * @var null
	 */
	protected static $table_prefix = null;

	/**
	 * Clean a set of filters by checking them against an array of allowed filters
	 *
	 * This is similar to an array intersect, if a $filter is present in $allowed and set to true,
	 * then it is an allowed filter.
	 *
	 * $filters = [
	 *        'foo' => 'bar',
	 *        'and' => [
	 *            'baz' => 'bat'
	 *            'or' => [
	 *                'bag' => 'boo'
	 *            ]
	 *        ],
	 *        'or' => [
	 *            'bar' => 'foo'
	 *        ],
	 * ];
	 *
	 * $allowed = [
	 *        'foo' => true,
	 *        'baz' => true,
	 *        'baz' => true,
	 *        'bar' => true,
	 * ];
	 *
	 * $result = [
	 *        'foo' => 'bar',
	 *        'and' => [
	 *            'baz' => 'bat'
	 *        ],
	 *        'or' => [
	 *            'bar' => 'foo'
	 *        ],
	 * ];
	 *
	 * @param array $filters
	 * @param array $allowed
	 *
	 * @return array
	 */
	public static function intersectAllowedFilters(array $filters, array $allowed)
	{
		foreach ($filters as $filter => $value) {
			// We want to recursively go down and check all OR conjuctions to ensure they're all whitlisted
			if ($filter === 'or') {
				$filters['or'] = self::intersectAllowedFilters($filters['or'], $allowed);

				// If there are no more filters under this OR, we can safely unset it
				if (count($filters['or']) === 0) {
					unset($filters['or']);
				}
				continue;
			}

			// We want to recursively go down and check all AND conjuctions to ensure they're all whitlisted
			if ($filter === 'and') {
				$filters['and'] = self::intersectAllowedFilters($filters['and'], $allowed);

				// If there are no more filters under this AND, we can safely unset it
				if (count($filters['and']) === 0) {
					unset($filters['and']);
				}
				continue;
			}

			// A whitelisted filter looks like 'filter_name' => true in $allowed
			if (! isset($allowed[$filter]) || ! $allowed[$filter]) {
				unset($filters[$filter]);
			}
		}

		return $filters;
	}

	/**
	 * Funnel for rest of filter methods
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array                                 $filters
	 * @param array                                 $columns
	 * @param string                                $table
	 */
	public static function applyQueryFilters($query, $filters, $columns, $table)
	{
		// Wrap in a complex where so we don't break soft delete checks
		$query->where(
			function ($query) use ($filters, $columns, $table) {
				self::filterQuery($query, $filters, $columns, $table);
			});
	}

	/**
	 * Funnel method to filter queries.
	 *
	 * First check for a dot nested string in the place of a filter column and use the appropriate method
	 * and relation combination.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array                                 $filters
	 * @param array                                 $columns
	 * @param string                                $table
	 */
	protected static function filterQuery($query, $filters, $columns, $table)
	{
		if (! is_null($table)) {
			self::$table_prefix = $table;
		}

		foreach ($filters as $column => $filter) {
			if (strtolower($column) === 'or' || strtolower($column) === 'and') {
				$nextConjunction = $column === 'or';
				$method          = self::determineMethod('where', $nextConjunction);

				// orWhere should only occur on conjunctions. We want filters in the same nesting level to attach as
				// 'AND'. 'OR' should nest.
				$query->$method(
					function ($query) use ($filters, $columns, $column, $table) {
						self::filterQuery($query, $filters[$column], $columns, $table);
					});
				continue;
			}

			$nested_relations = self::parseRelations($column);

			if (is_array($nested_relations)) {
				// Create a dot nested string of relations
				$relation = implode('.', array_splice($nested_relations, 0, count($nested_relations) - 1));
				// Set up the column at the end of the dot nested relation
				$column = end($nested_relations);
			}

			if ($token = self::determineTokenType($filter)) {
				// We check to see if the filter string is a valid filter.
				$filter = self::cleanAndValidateFilter($token, $filter);

				// If it is not a valid filter we continue to the next
				// iteration in the array.
				if ($filter === false) {
					continue;
				}

				$method = self::$supported_tokens[$token];

				// Querying a dot nested relation
				if (is_array($nested_relations)) {

					$query->whereHas(
						$relation, function ($query) use ($method, $column, $filter) {

						// Check if the column is a primary key of the model
						// within the query. If it is, we should use the
						// qualified key instead. It's important when this is a
						// many to many relationship query.
						if ($column === $query->getModel()->getKeyName()) {
							$column = $query->getModel()->getQualifiedKeyName();
						}

						self::$method($column, $filter, $query);
					});
				} else {
					$column = self::applyTablePrefix($column);
					self::$method($column, $filter, $query);
				}
			} elseif ($filter === 'true' || $filter === 'false') {
				// Is a boolean filter, coerce to boolean.
				$filter = ($filter === 'true');

				// Querying a dot nested relation
				if (is_array($nested_relations)) {
					$query->whereHas(
						$relation, function ($query) use ($filter, $column) {
						$where = camel_case('where' . $column);
						$query->$where($filter);
					});
				} else {
					$column = self::applyTablePrefix($column);
					$where  = camel_case('where' . $column);
					$query->$where($filter);
				}
			} elseif ($filter === 'NULL' || $filter === 'NOT_NULL') {
				// Querying a dot nested relation
				if (is_array($nested_relations)) {
					$query->whereHas(
						$relation, function ($query) use ($column, $filter) {
						self::nullMethod($column, $filter, $query);
					});
				} else {
					$column = self::applyTablePrefix($column);
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
	 *
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
	 * @param bool                                  $or
	 */
	protected static function startsWith($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, 'LIKE', $filter . '%');
	}

	/**
	 * Query for items that end with a string.
	 *
	 * Ex: users?filters[name]=$Smith
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function endsWith($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, 'LIKE', '%' . $filter);
	}

	/**
	 * Query for items that contain a string.
	 *
	 * Ex: users?filters[favorite_cheese]=~cheddar
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function contains($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, 'LIKE', '%' . $filter . '%');
	}

	/**
	 * Query for items with a value less than a filter.
	 *
	 * Ex: users?filters[lifetime_value]=<50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function lessThan($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, '<', $filter);
	}

	/**
	 * Query for items with a value greater than a filter.
	 *
	 * Ex: users?filters[lifetime_value]=>50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function greaterThan($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, '>', $filter);
	}

	/**
	 * Query for items with a value greater than or equal to a filter.
	 *
	 * Ex: users?filters[lifetime_value]=>=50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function greaterThanOrEquals($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, '>=', $filter);
	}

	/**
	 * Query for items with a value less than or equal to a filter.
	 *
	 * Ex: users?filters[lifetime_value]=<=50
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function lessThanOrEquals($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, '<=', $filter);
	}

	/**
	 * Query for items with a value equal to a filter.
	 *
	 * Ex: users?filters[username]==Specific%20Username
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function equals($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);

		if ($filter === 'true' || $filter === 'false') {
			$filter = $filter === 'true';
		}

		$query->$method($column, '=', $filter);
	}

	/**
	 * Query for items with a value not equal to a filter.
	 *
	 * Ex: users?filters[username]=!=common%20username
	 *
	 * @param string                                $column
	 * @param string                                $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function notEquals($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('where', $or);
		$query->$method($column, '!=', $filter);
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
	 * @param bool                                  $or
	 */
	protected static function nullMethod($column, $filter, $query, $or = false)
	{
		if ($filter === 'NULL') {
			$method = self::determineMethod('whereNull', $or);
			$query->$method($column);
		} else {
			$method = self::determineMethod('whereNotNull', $or);
			$query->$method($column);
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
	 * @param bool                                  $or
	 */
	protected static function in($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('whereIn', $or);
		$query->$method($column, $filter);
	}

	/**
	 * Query for items that are not in a list.
	 *
	 * Ex: users?filters[id]=![1,5,10]
	 *
	 * @param string                                $column
	 * @param string|array                          $filter
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param bool                                  $or
	 */
	protected static function notIn($column, $filter, $query, $or = false)
	{
		$method = self::determineMethod('whereNotIn', $or);
		$query->$method($column, $filter);
	}

	/**
	 * Determine the token (if any) to use for the query
	 *
	 * @param string $filter
	 *
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
	 *
	 * @return bool
	 */
	private static function shouldBeScalar($token)
	{
		// Is token in array of tokens that can be non-scalar
		return ! in_array($token, self::$non_scalar_tokens);
	}

	/**
	 * Parse a filter string and confirm that it has a scalar value if it should.
	 *
	 * @param string $token
	 * @param string $filter
	 *
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
			$filter = $filter[0];
		}

		return $filter;
	}

	/**
	 * Determine whether to apply a table prefix to prevent ambiguous columns
	 *
	 * @param $column
	 *
	 * @return string
	 */
	private static function applyTablePrefix($column)
	{
		return is_null(self::$table_prefix) ? $column : self::$table_prefix . '.' . $column;
	}

	/**
	 * Determine whether this an 'or' method or not
	 *
	 * @param string $base_name
	 * @param bool   $or
	 *
	 * @return string
	 */
	private static function determineMethod($base_name, $or)
	{
		return $or ? camel_case('or_' . $base_name) : $base_name;
	}
}
