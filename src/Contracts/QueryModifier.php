<?php

namespace Fuzz\MagicBox\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interface QueryModifier
 *
 * A QueryModifier applies query modifications such as eager loads, groupings, aggregations, etc to a query.
 *
 * @package Fuzz\MagicBox\Contracts
 */
interface QueryModifier
{
	/**
	 * The glue for nested strings
	 *
	 * @var string
	 */
	const GLUE = '.';

	/**
	 * Store the query
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setQuery(Builder $query): QueryModifier;

	/**
	 * Process filter and sort modifications on $query
	 *
	 * @param \Fuzz\MagicBox\Contracts\AccessControl $access_compiler
	 * @param string                                 $model_class
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier|void
	 */
	public function apply(AccessControl $access_compiler, string $model_class): QueryModifier;

	/**
	 * "Safe" version of with eager-loading.
	 *
	 * Checks if relations exist before loading them.
	 *
	 * @param \Fuzz\MagicBox\Contracts\AccessControl $access_compiler
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function applyEagerLoads(AccessControl $access_compiler): QueryModifier;

	/**
	 * Apply all query modifiers to the query
	 *
	 * @return mixed
	 */
	public function applyModifiers(): QueryModifier;

	/**
	 * Set eager load manually.
	 *
	 * @param array $eager_loads
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setEagerLoads(array $eager_loads): QueryModifier;

	/**
	 * Get eager loads.
	 *
	 * @return array
	 */
	public function getEagerLoads(): array;

	/**
	 * Set filters manually.
	 *
	 * @param array $filters
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setFilters(array $filters): QueryModifier;

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function getFilters(): array;

	/**
	 * Add filters to already existing filters without overwriting them.
	 *
	 * @param array $filters
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function addFilters(array $filters);

	/**
	 * Add a single filter to already existing filters without overwriting them.
	 *
	 * @param string $key
	 * @param string $value
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function addFilter(string $key, string $value);

	/**
	 * Add a single modifier
	 *
	 * @param \Closure $modifier
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function add(Closure $modifier): QueryModifier;

	/**
	 * Set modifiers.
	 *
	 * @param array $modifiers
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function set(array $modifiers): QueryModifier;

	/**
	 * Get modifiers.
	 *
	 * @return array
	 */
	public function getModifiers(): array;

	/**
	 * Get group by.
	 *
	 * @return array
	 */
	public function getGroupBy(): array;

	/**
	 * Set group by manually.
	 *
	 * @param array $group_by
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setGroupBy(array $group_by): QueryModifier;

	/**
	 * Get available aggregate
	 *
	 * @return array
	 */
	public function getAggregate(): array;

	/**
	 * Set aggregate functions.
	 *
	 * @param array $aggregate
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setAggregate(array $aggregate): QueryModifier;

	/**
	 * Set sort order manually.
	 *
	 * @param array $sort_order
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setSortOrder(array $sort_order): QueryModifier;

	/**
	 * Get sort order.
	 *
	 * @return array
	 */
	public function getSortOrder(): array;
}