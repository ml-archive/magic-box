<?php

namespace Fuzz\MagicBox;

use Closure;
use Fuzz\MagicBox\Contracts\AccessControl;
use Fuzz\MagicBox\Contracts\QueryModifier;
use Fuzz\MagicBox\Utility\ChecksModelFields;
use Fuzz\MagicBox\Utility\ChecksRelations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class EloquentQueryModifier
 *
 * A QueryModifier implementation for Eloquent.
 *
 * @package Fuzz\MagicBox
 */
class EloquentQueryModifier implements QueryModifier
{
	use ChecksRelations, ChecksModelFields;

	/**
	 * Storage for filters.
	 *
	 * @var array
	 */
	private $filters = [];

	/**
	 * Storage for eager loads.
	 *
	 * @var array
	 */
	private $eager_loads = [];

	/**
	 * Storage for query modifiers.
	 *
	 * @var array
	 */
	private $modifiers = [];

	/**
	 * Storage for sort order.
	 *
	 * @var array
	 */
	private $sort_order = [];

	/**
	 * Storage for group by.
	 *
	 * @var array
	 */
	private $group_by = [];

	/**
	 * Storage for aggregate functions.
	 *
	 * @var array
	 */
	private $aggregate = [];

	/**
	 * Query storage
	 *
	 * @var \Illuminate\Database\Eloquent\Builder
	 */
	private $query;

	/**
	 * Store the query
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setQuery(Builder $query): QueryModifier
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * Access the query
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function query(): Builder
	{
		return $this->query;
	}

	/**
	 * Set eager load manually.
	 *
	 * @param array $eager_loads
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setEagerLoads(array $eager_loads): QueryModifier
	{
		$this->eager_loads = $eager_loads;

		return $this;
	}

	/**
	 * Get eager loads.
	 *
	 * @return array
	 */
	public function getEagerLoads(): array
	{
		return $this->eager_loads;
	}

	/**
	 * Set filters manually.
	 *
	 * @param array $filters
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setFilters(array $filters): QueryModifier
	{
		$this->filters = $filters;

		return $this;
	}

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}

	/**
	 * Add filters to already existing filters without overwriting them.
	 *
	 * @param array $filters
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function addFilters(array $filters): QueryModifier
	{
		foreach ($filters as $key => $value) {
			$this->addFilter($key, $value);
		}

		return $this;
	}

	/**
	 * Add a single filter to already existing filters without overwriting them.
	 *
	 * @param string $key
	 * @param string $value
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function addFilter(string $key, string $value): QueryModifier
	{
		$this->filters[$key] = $value;

		return $this;
	}

	/**
	 * Add a single modifier
	 *
	 * @param \Closure $modifier
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function add(Closure $modifier): QueryModifier
	{
		$this->modifiers[] = $modifier;

		return $this;
	}

	/**
	 * Set modifiers.
	 *
	 * @param array $modifiers
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function set(array $modifiers): QueryModifier
	{
		$this->modifiers = $modifiers;

		return $this;
	}

	/**
	 * Get modifiers.
	 *
	 * @return array
	 */
	public function getModifiers(): array
	{
		return $this->modifiers;
	}

	/**
	 * Get group by.
	 *
	 * @return array
	 */
	public function getGroupBy(): array
	{
		return $this->group_by;
	}

	/**
	 * Set group by manually.
	 *
	 * @param array $group_by
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setGroupBy(array $group_by): QueryModifier
	{
		$this->group_by = $group_by;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAggregate(): array
	{
		return $this->aggregate;
	}

	/**
	 * Set aggregate functions.
	 *
	 * @param array $aggregate
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setAggregate(array $aggregate): QueryModifier
	{
		$this->aggregate = $aggregate;

		return $this;
	}

	/**
	 * Set sort order manually.
	 *
	 * @param array $sort_order
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function setSortOrder(array $sort_order): QueryModifier
	{
		$this->sort_order = $sort_order;

		return $this;
	}

	/**
	 * Get sort order.
	 *
	 * @return array
	 */
	public function getSortOrder(): array
	{
		return $this->sort_order;
	}

	/**
	 * Apply filters to the query
	 *
	 * @param array                                  $filters
	 * @param \Fuzz\MagicBox\Contracts\AccessControl $access_compiler
	 * @param array                                  $columns
	 * @param \Illuminate\Database\Eloquent\Model    $temp_instance
	 */
	protected function filterQuery(array $filters, AccessControl $access_compiler, array $columns, Model $temp_instance)
	{
		// Apply depth restrictions to each filter
		foreach ($filters as $filter => $value) {
			// Filters deeper than the depth restriction + 1 are not allowed
			// Depth restriction is offset by 1 because filters terminate with a column
			// i.e. 'users.posts.title' => '=Great Post' but the depth we expect is 'users.posts'
			if (count(explode(self::GLUE, $filter)) > ($access_compiler->getDepthRestriction() + 1)) {
				// Unset the disallowed filter
				unset($filters[$filter]);
			}
		}

		Filter::applyQueryFilters($this->query(), $filters, $columns, $temp_instance->getTable());
	}

	/**
	 * Modify the query with a group by condition.
	 *
	 * @param array $group_by
	 * @param array  $columns
	 *
	 * @return array
	 */
	protected function groupQueryBy(array $group_by, array $columns): array
	{
		$group = explode(',', reset($group_by));
		$group = array_map('trim', $group);
		$valid_group = array_intersect($group, $columns);

		$this->query()->groupBy($valid_group);

		return $valid_group;
	}

	/**
	 * Run an aggregate function. We will only run one, no matter how many were submitted.
	 *
	 * @param array $aggregate
	 * @param array $columns
	 * @param array $valid_group
	 */
	protected function aggregateQuery(array $aggregate, array $columns, array $valid_group = [])
	{
		$allowed_aggregations = [
			'count',
			'min',
			'max',
			'sum',
			'avg',
		];
		$allowed_columns = $columns;
		$column = reset($aggregate);
		$function = strtolower(key($aggregate));

		if (in_array($function, $allowed_aggregations, true) && in_array($column, $allowed_columns, true)) {
			$this->query()->addSelect(DB::raw($function . '(' . $column . ') as aggregate'));

			if (! empty($valid_group)) {
				$this->query()->addSelect($valid_group);
			}
		}
	}

	/**
	 * Process filter and sort modifications on $query
	 *
	 * @param \Fuzz\MagicBox\Contracts\AccessControl $access_compiler
	 * @param string                                 $model_class
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function apply(AccessControl $access_compiler, string $model_class): QueryModifier
	{
		// Only include filters which have been whitelisted in $this->filterable
		$filters            = $access_compiler->getFilterable() === AccessControl::ALLOW_ALL ? $this->getFilters() :
			Filter::intersectAllowedFilters($this->getFilters(), $access_compiler->getFilterable(true));
		$sort_order_options = $this->getSortOrder();
		$group_by           = $this->getGroupBy();
		$aggregate          = $this->getAggregate();

		// Check if filters or sorts are requested
		$filters_exist   = ! empty($filters);
		$sorts_exist     = ! empty($sort_order_options);
		$group_exist     = ! empty($group_by);
		$aggregate_exist = ! empty($aggregate);

		// No modifications to apply
		if (! $filters_exist && ! $sorts_exist && ! $group_exist && ! $aggregate_exist) {
			return $this;
		}

		// Make a mock instance so we can describe its columns
		$temp_instance = new $model_class;
		$columns = $this->getFields($temp_instance);

		if ($filters_exist) {
			$this->filterQuery($filters, $access_compiler, $columns, $temp_instance);
		}

		$valid_group = [];

		if ($group_exist) {
			$valid_group = $this->groupQueryBy($group_by, $columns);
		}

		if ($aggregate_exist) {
			$this->aggregateQuery($aggregate, $columns, $valid_group);
		}

		if ($sorts_exist) {
			$this->sortQuery($sort_order_options, $temp_instance, $columns, $access_compiler->getDepthRestriction());
		}

		unset($temp_instance);

		return $this;
	}

	/**
	 * Apply all query modifiers to the query
	 *
	 * @return mixed
	 */
	public function applyModifiers(): QueryModifier
	{
		$query     = $this->query();
		$modifiers = $this->getModifiers();

		foreach ($modifiers as $modifier) {
			$modifier($query);
		}

		return $this;
	}

	/**
	 * "Safe" version of with eager-loading.
	 *
	 * Checks if relations exist before loading them.
	 *
	 * @param \Fuzz\MagicBox\Contracts\AccessControl $access_compiler
	 *
	 * @return \Fuzz\MagicBox\Contracts\QueryModifier
	 */
	public function applyEagerLoads(AccessControl $access_compiler): QueryModifier
	{
		$query = $this->query();
		$relations =$this->getEagerLoads();

		if (is_string($relations)) {
			$relations = func_get_args();
			array_shift($relations);
		}

		// Loop through all relations to check for valid relationship signatures
		foreach ($relations as $name => $constraints) {
			// Constraints may be passed in either form:
			// 2 => 'relation.nested'
			// or
			// 'relation.nested' => function() { ... }
			$constraints_are_name = is_numeric($name);
			$relation_name = $constraints_are_name ? $constraints : $name;

			// If this relation is not includable, skip
			// We expect to see foo.nested.relation in includable if the 3 level nested relationship is includable
			if (! $access_compiler->isIncludable($relation_name)) {
				unset($relations[$name]);
				continue;
			}

			// Expand the dot-notation to see all relations
			$nested_relations = explode(self::GLUE, $relation_name);
			$model = $query->getModel();

			// Don't allow eager loads beyond the eager load depth
			$nested_relations = $access_compiler->applyDepthRestriction($nested_relations);

			// We want to apply the depth restricted relations to the original relations array
			$cleaned_relation = join(self::GLUE, $nested_relations);
			if ($cleaned_relation === '') {
				unset($relations[$name]);
			} elseif ($constraints_are_name) {
				$relations[$name] = $cleaned_relation;
			} else {
				$relations[$cleaned_relation] = $constraints;
				unset($relations[$name]);
			}

			foreach ($nested_relations as $index => $relation) {

				if ($this->isRelation($model, $relation, get_class($model))) {
					// Iterate through relations if they actually exist
					$model = $model->$relation()->getRelated();
				} elseif ($index > 0) {
					// If we found any valid relations, pass them through
					$safe_relation = implode(self::GLUE, array_slice($nested_relations, 0, $index));
					if ($constraints_are_name) {
						$relations[$name] = $safe_relation;
					} else {
						unset($relations[$name]);
						$relations[$safe_relation] = $constraints;
					}
				} else {
					// If we didn't, remove this relation specification
					unset($relations[$name]);
					break;
				}
			}
		}

		$query->with($relations);

		return $this;
	}

	/**
	 * Apply a sort to a database query
	 *
	 * @param array                               $sort_order_options
	 * @param \Illuminate\Database\Eloquent\Model $temp_instance
	 * @param array                               $columns
	 * @param int                                 $depth_restriction
	 */
	protected function sortQuery(array $sort_order_options, Model $temp_instance, array $columns, int $depth_restriction)
	{
		$query = $this->query();

		$allowed_directions = [
			'ASC',
			'DESC',
		];

		foreach ($sort_order_options as $order_by => $direction) {
			if (in_array(strtoupper($direction), $allowed_directions)) {
				$split = explode(self::GLUE, $order_by);

				// Sorts deeper than the depth restriction + 1 are not allowed
				// Depth restriction is offset by 1 because sorts terminate with a column
				// i.e. 'users.posts.title' => 'asc' but the depth we expect is 'users.posts'
				if (count($split) > ($depth_restriction + 1)) {
					// Unset the disallowed sort
					unset($sort_order_options[$order_by]);
					continue;
				}

				if (in_array($order_by, $columns)) {
					$query->orderBy($order_by, $direction);
				} else {
					// Pull out orderBy field
					$field = array_pop($split);

					// Select only the base table fields, don't select relation data. Desired relation data
					// should be explicitly included
					$base_table = $temp_instance->getTable();
					$query->selectRaw("$base_table.*");

					$this->applyNestedJoins($split, $temp_instance, $field, $direction);
				}
			}
		}
	}

	/**
	 * Apply nested joins to allow nested sorting for select relationship combinations
	 *
	 * @param array $relations
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @param                                       $field
	 * @param string $direction
	 * @return void
	 */
	protected function applyNestedJoins(array $relations, Model $instance, $field, $direction = 'asc')
	{
		$query = $this->query();

		$base_table = $instance->getTable();

		// The current working relation
		$relation = $relations[0];

		// Current working table
		$table = Str::plural($relation);
		$singular = Str::singular($relation);
		$class = get_class($instance);

		// If the relation exists, determine which type (singular, multiple)
		if ($this->isRelation($instance, $singular, $class)) {
			$related = $instance->$singular();
		} elseif ($this->isRelation($instance, $relation, $class)) {
			$related = $instance->$relation();
		} else {
			// This relation does not exist
			return;
		}

		$foreign_key = $related->getForeignKey();

		// Join tables differently depending on relationship type
		switch (get_class($related)) {
			case BelongsToMany::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $related
				 */
				$base_table_key = $instance->getKeyName();
				$relation_primary_key = $related->getModel()->getKeyName();

				// Join through the pivot table
				$query->join($related->getTable(), "$base_table.$base_table_key", '=', $foreign_key);
				$query->join($table, $related->getOtherKey(), '=', "$relation.$relation_primary_key");
				break;
			case HasMany::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\HasMany $related
				 */
				$base_table_key = $instance->getKeyName();

				// Join child's table
				$query->join($table, "$base_table.$base_table_key", '=', $foreign_key);
				break;
			case BelongsTo::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $related
				 */
				$relation_key = $related->getOtherKey();

				// Join related's table on the base table's foreign key
				$query->join($table, "$base_table.$foreign_key", '=', "$table.$relation_key");
				break;
			case HasOne::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\HasOne $related
				 */
				$parent_key = $instance->getKeyName();

				// Join related's table on the base table's foreign key
				$query->join($table, "$base_table.$parent_key", '=', "$foreign_key");
				break;
		}

		// @todo is it necessary to allow nested relationships further than the first/second degrees?
		array_shift($relations);

		if (count($relations) >= 1) {
			$this->applyNestedJoins($relations, $related->getModel(), $field, $direction);
		} else {
			$query->orderBy("$table.$field", $direction);
		}
	}
}