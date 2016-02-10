<?php

namespace Fuzz\MagicBox\Contracts;

interface FilterInterface
{
	/**
	 * Funnel for rest of filter methods
	 *
	 * Applies filters wrapped in a complex where.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array                                 $filters
	 * @param array                                 $columns
	 * @param string                                $table
	 */
	public static function applyQueryFilters($query, $filters, $columns, $table);

	/**
	 * Funnel method to filter queries.
	 *
	 * Directly applies filters without protected from a complex where.
	 *
	 * First check for a dot nested string in the place of a filter column and use the appropriate method
	 * and relation combination.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array                                 $filters
	 * @param array                                 $columns
	 * @param string                                $table
	 */
	public static function filterQuery($query, $filters, $columns, $table);
}
