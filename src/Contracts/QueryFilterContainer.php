<?php

namespace Fuzz\MagicBox\Contracts;

/**
 * Interface QueryFilterContainer
 *
 * A QueryFilterContainer modifies an Eloquent query and applies filters to it.
 *
 * @package Fuzz\MagicBox\Contracts
 */
interface QueryFilterContainer
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
}
