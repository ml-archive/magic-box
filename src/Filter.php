<?php

namespace Fuzz\MagicBox;

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
		'['  => 'in'
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
	 * Retrieve the token
	 *
	 * @param $filter
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
	 * @param $token
	 * @return bool
	 */
	private static function shouldBeScalar($token)
	{
		// Is token in array of tokens that can be non-scalar
		return ! in_array($token, self::$non_scalar_tokens);
	}

	/**
	 * Funnel method to filter queries
	 *
	 * @param $query
	 * @param $filters
	 * @param $model_columns
	 */
	public static function filterQuery($query, $filters, $model_columns)
	{
		foreach ($filters as $column => $filter) {
			// Not a model attribute
			if (! in_array($column, $model_columns)) {
				// Possibly accessed via a mutator - need to handle
				continue;
			} elseif ($token = self::determineTokenType($filter)) {
				// Is a supported method token, run logic
				$filter = explode(',', substr($filter, strlen($token)));

				// If this should be a scalar value but is not, don't process as a filter
				if (self::shouldBeScalar($token)) {
					if (count($filter) > 1) {
						continue;
					}

					// Set to first index if should be scalar
					$filter = $filter[0];
				}

				$method = self::$supported_tokens[$token];

				self::$method($column, $filter, $query);
			} elseif ($filter === 'true' || $filter === 'false') {
				// Is a boolean filter, coerce to boolean.
				$filter = ($filter === 'true');
				$where  = camel_case('where' . $column);
				$query->$where($filter);
			} elseif ($filter === 'NULL' || $filter === 'NOT_NULL') {
				self::nullMethod($column, $filter, $query);
			} else {
				// @todo Unsupported type
			}
		}
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function startsWith($column, $filter, $query)
	{
		$query->where($column, 'LIKE', $filter . '%');
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function endsWith($column, $filter, $query)
	{
		$query->where($column, 'LIKE', '%' . $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function contains($column, $filter, $query)
	{
		$query->where($column, 'LIKE', '%' . $filter . '%');
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function lessThan($column, $filter, $query)
	{
		$query->where($column, '<', $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function greaterThan($column, $filter, $query)
	{
		$query->where($column, '>', $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function greaterThanOrEquals($column, $filter, $query)
	{
		$query->where($column, '>=', $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function lessThanOrEquals($column, $filter, $query)
	{
		$query->where($column, '<=', $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function equals($column, $filter, $query)
	{
		$where = camel_case('where' . $column);
		$query->$where($filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function notEquals($column, $filter, $query)
	{
		$query->where($column, '!=', $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
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
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function in($column, $filter, $query)
	{
		$query->whereIn($column, $filter);
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $query
	 */
	protected static function notIn($column, $filter, $query)
	{
		$query->whereNotIn($column, $filter);
	}
}
