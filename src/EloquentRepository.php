<?php

namespace Fuzz\MagicBox;

use Closure;
use Fuzz\MagicBox\Utility\ChecksRelations;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Fuzz\MagicBox\Contracts\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EloquentRepository implements Repository
{
	use ChecksRelations;

	/**
	 * Value that defines allowing all fields/relationships in includable/filterable/fillable
	 *
	 * @const array
	 */
	const ALLOW_ALL = ['*'];

	/**
	 * An instance variable specifying the model handled by this repository.
	 *
	 * @var string
	 */
	private $model_class;

	/**
	 * An instance variable specifying the input passed to model instances.
	 *
	 * @var array
	 */
	private $input = [];

	/**
	 * Storage for filters.
	 *
	 * @var array
	 */
	private $filters = [];

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
	 * Storage for eager loads.
	 *
	 * @var array
	 */
	private $eager_loads = [];

	/**
	 * How many levels deep relationships can be included.
	 *
	 * @var int
	 */
	protected $depth_restriction = 0;

	/**
	 * Storage for query modifiers.
	 *
	 * @var array
	 */
	private $modifiers = [];

	/**
	 * Attributes on the model which can be modified by the repository
	 *
	 * @var array
	 */
	private $fillable = [];

	/**
	 * Relationships that can be included with the repository
	 *
	 * @var array
	 */
	private $includable = [];

	/**
	 * Fields that can be filtered on the repository
	 *
	 * @var array
	 */
	private $filterable = [];

	/**
	 * PK name
	 *
	 * @var string
	 */
	private $key_name = 'id';

	/**
	 * The glue for nested strings
	 *
	 * @var string
	 */
	const GLUE = '.';

	/**
	 * Set the model for an instance of this resource controller.
	 *
	 * @param string $model_class
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setModelClass($model_class): Repository
	{
		if ( !is_subclass_of($model_class, Model::class)) {
			throw new InvalidArgumentException('Specified model class must be an instance of ' . Model::class);
		}

		$this->model_class = $model_class;

		/** @var \Illuminate\Database\Eloquent\Model|\Fuzz\MagicBox\Contracts\MagicBoxResource $instance */
		$instance = new $model_class;

		// @todo use set methods
		$this->setFillable($instance->getRepositoryFillable());
		$this->setIncludable($instance->getRepositoryIncludable());
		$this->setFilterable($instance->getRepositoryFilterable());

		$this->key_name = $instance->getKeyName();

		return $this;
	}

	/**
	 * Get the PK name
	 *
	 * @return string
	 */
	public function getKeyName(): string
	{
		return $this->key_name;
	}

	/**
	 * Determine if the model exists
	 *
	 * @return bool
	 */
	public function exists(): bool
	{
		return array_key_exists($this->getKeyName(), $this->getInput());
	}

	/**
	 * Get the model class.
	 *
	 * @return string
	 */
	public function getModelClass(): string
	{
		return $this->model_class;
	}

	/**
	 * Set input manually.
	 *
	 * @param array $input
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setInput(array $input): Repository
	{
		$this->input = $input;

		return $this;
	}

	/**
	 * Get input.
	 *
	 * @return array
	 */
	public function getInput(): array
	{
		return $this->input;
	}

	/**
	 * Set eager load manually.
	 *
	 * @param array $eager_loads
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setEagerLoads(array $eager_loads): Repository
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
	 * Get the eager load depth property.
	 *
	 * @return int
	 */
	public function getDepthRestriction(): int
	{
		return $this->depth_restriction;
	}

	/**
	 * Set the eager load depth property.
	 * This will limit how deep relationships can be included.
	 *
	 * @param int $depth
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setDepthRestriction($depth): Repository
	{
		$this->depth_restriction = $depth;

		return $this;
	}

	/**
	 * Set filters manually.
	 *
	 * @param array $filters
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setFilters(array $filters): Repository
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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addFilters(array $filters): Repository
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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addFilter(string $key, string $value): Repository
	{
		$this->filters[$key] = $value;

		return $this;
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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setGroupBy(array $group_by): Repository
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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setAggregate(array $aggregate): Repository
	{
		$this->aggregate = $aggregate;

		return $this;
	}

	/**
	 * Set sort order manually.
	 *
	 * @param array $sort_order
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setSortOrder(array $sort_order): Repository
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
	 * Add a single modifier
	 *
	 * @param \Closure $modifier
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addModifier(Closure $modifier): Repository
	{
		$this->modifiers[] = $modifier;

		return $this;
	}

	/**
	 * Set modifiers.
	 *
	 * @param array $modifiers
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setModifiers(array $modifiers): Repository
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
	 * Set the fillable array
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setFillable(array $fillable): Repository
	{
		if ($fillable === self::ALLOW_ALL) {
			$this->fillable = self::ALLOW_ALL;

			return $this;
		}

		// Reset fillable
		$this->fillable = [];

		foreach ($fillable as $allowed_field) {
			$this->fillable[$allowed_field] = true;
		}

		return $this;
	}

	/**
	 * Get the fillable attributes
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getFillable(bool $assoc = false): array
	{
		if ($this->fillable === self::ALLOW_ALL) {
			return self::ALLOW_ALL;
		}

		return $assoc ? $this->fillable : array_keys($this->fillable);
	}

	/**
	 * Add a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addFillable(string $fillable): Repository
	{
		$this->fillable[$fillable] = true;

		return $this;
	}

	/**
	 * Add many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addManyFillable(array $fillable): Repository
	{
		foreach ($fillable as $allowed_field) {
			$this->addFillable($allowed_field);
		}

		return $this;
	}

	/**
	 * Remove a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeFillable(string $fillable): Repository
	{
		unset($this->fillable[$fillable]);

		return $this;
	}

	/**
	 * Remove many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeManyFillable(array $fillable): Repository
	{
		foreach ($fillable as $disallowed_field) {
			$this->removeFillable($disallowed_field);
		}

		return $this;
	}

	/**
	 * Determine whether a given key is fillable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFillable(string $key): bool
	{
		if ($this->fillable === self::ALLOW_ALL) {
			return true;
		}

		return isset($this->fillable[$key]) && $this->fillable[$key];
	}

	/**
	 * Set the relationships which can be included by the model
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setIncludable(array $includable): Repository
	{
		if ($includable === self::ALLOW_ALL) {
			$this->includable = self::ALLOW_ALL;

			return $this;
		}

		// Reset includable
		$this->includable = [];

		foreach ($includable as $allowed_include) {
			$this->includable[$allowed_include] = true;
		}

		return $this;
	}

	/**
	 * Get the includable relationships
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getIncludable(bool $assoc = false): array
	{
		if ($this->includable === self::ALLOW_ALL) {
			return self::ALLOW_ALL;
		}

		return $assoc ? $this->includable : array_keys($this->includable);
	}

	/**
	 * Add an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addIncludable(string $includable): Repository
	{
		$this->includable[$includable] = true;

		return $this;
	}

	/**
	 * Add many includable fields
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addManyIncludable(array $includable): Repository
	{
		foreach ($includable as $allowed_include) {
			$this->addIncludable($allowed_include);
		}

		return $this;
	}

	/**
	 * Remove an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeIncludable(string $includable): Repository
	{
		unset($this->includable[$includable]);

		return $this;
	}

	/**
	 * Remove many includable relationships
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeManyIncludable(array $includable): Repository
	{
		foreach ($includable as $disallowed_include) {
			$this->removeIncludable($disallowed_include);
		}

		return $this;
	}

	/**
	 * Determine whether a given key is includable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isIncludable(string $key): bool
	{
		if ($this->includable === self::ALLOW_ALL) {
			return true;
		}

		return isset($this->includable[$key]) && $this->includable[$key];
	}

	/**
	 * Set the fields which can be filtered on the model
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setFilterable(array $filterable): Repository
	{
		if ($filterable === self::ALLOW_ALL) {
			$this->filterable = self::ALLOW_ALL;

			return $this;
		}

		// Reset filterable
		$this->filterable = [];

		foreach ($filterable as $allowed_field) {
			$this->filterable[$allowed_field] = true;
		}

		return $this;
	}

	/**
	 * Get the filterable fields
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getFilterable(bool $assoc = false): array
	{
		if ($this->filterable === self::ALLOW_ALL) {
			return self::ALLOW_ALL;
		}

		return $assoc ? $this->filterable : array_keys($this->filterable);
	}

	/**
	 * Add a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addFilterable(string $filterable): Repository
	{
		$this->filterable[$filterable] = true;

		return $this;
	}

	/**
	 * Add many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addManyFilterable(array $filterable): Repository
	{
		foreach ($filterable as $allowed_field) {
			$this->addFilterable($allowed_field);
		}

		return $this;
	}

	/**
	 * Remove a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeFilterable(string $filterable): Repository
	{
		unset($this->filterable[$filterable]);

		return $this;
	}

	/**
	 * Remove many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeManyFilterable(array $filterable): Repository
	{
		foreach ($filterable as $disallowed_field) {
			$this->removeFilterable($disallowed_field);
		}

		return $this;
	}

	/**
	 * Determine whether a given key is filterable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFilterable(string $key): bool
	{
		if ($this->filterable === self::ALLOW_ALL) {
			return true;
		}

		return isset($this->filterable[$key]) && $this->filterable[$key];
	}

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

	/**
	 * Base query for all behaviors within this repository.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function query()
	{
		$query = forward_static_call(
			[
				$this->getModelClass(),
				'query',
			]
		);

		$this->modifyQuery($query);

		$eager_loads = $this->getEagerLoads();

		if ( !empty($eager_loads)) {
			$this->safeWith($query, $eager_loads);
		}

		if ( !empty($modifiers = $this->getModifiers())) {
			foreach ($modifiers as $modifier) {
				$modifier($query);
			}
		}

		return $query;
	}

	/**
	 * Process filter and sort modifications on $query
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return void
	 */
	protected function modifyQuery($query)
	{
		// Only include filters which have been whitelisted in $this->filterable
		$filters = $this->getFilterable() === self::ALLOW_ALL ?
			$this->getFilters() :
			Filter::intersectAllowedFilters($this->getFilters(), $this->getFilterable(true));
		$sort_order_options = $this->getSortOrder();
		$group_by = $this->getGroupBy();
		$aggregate = $this->getAggregate();

		// Check if filters or sorts are requested
		$filters_exist = !empty($filters);
		$sorts_exist = !empty($sort_order_options);
		$group_exist = !empty($group_by);
		$aggregate_exist = !empty($aggregate);

		// No modifications to apply
		if ( !$filters_exist && !$sorts_exist && !$group_exist && !$aggregate_exist) {
			return;
		}

		// Make a mock instance so we can describe its columns
		$model_class = $this->getModelClass();
		$temp_instance = new $model_class;
		$columns = $this->getFields($temp_instance);

		if ($filters_exist) {
			// Apply depth restrictions to each filter
			foreach ($filters as $filter => $value) {
				// Filters deeper than the depth restriction + 1 are not allowed
				// Depth restriction is offset by 1 because filters terminate with a column
				// i.e. 'users.posts.title' => '=Great Post' but the depth we expect is 'users.posts'
				if (count(explode(self::GLUE, $filter)) > ($this->getDepthRestriction() + 1)) {
					// Unset the disallowed filter
					unset($filters[$filter]);
				}
			}

			Filter::applyQueryFilters($query, $filters, $columns, $temp_instance->getTable());
		}

		// Modify the query with a group by condition.
		if ($group_exist) {
			$group = explode(',', reset($group_by));
			$group = array_map('trim', $group);
			$valid_group = array_intersect($group, $columns);

			$query->groupBy($valid_group);
		}

		// Run an aggregate function. We will only run one, no matter how many were submitted.
		if ($aggregate_exist) {
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
				$query->addSelect(DB::raw($function . '(' . $column . ') as aggregate'));

				if ($group_exist) {
					$query->addSelect($valid_group);
				}
			}
		}

		if ($sorts_exist) {
			$this->sortQuery($query, $sort_order_options, $temp_instance, $columns);
		}

		unset($temp_instance);
	}

	/**
	 * Apply a sort to a database query
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array $sort_order_options
	 * @param \Illuminate\Database\Eloquent\Model $temp_instance
	 * @param array $columns
	 */
	protected function sortQuery(Builder $query, array $sort_order_options, Model $temp_instance, array $columns)
	{
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
				if (count($split) > ($this->getDepthRestriction() + 1)) {
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

					$this->applyNestedJoins($query, $split, $temp_instance, $field, $direction);
				}
			}
		}
	}

	/**
	 * Apply a depth restriction to an exploded dot-nested string (eager load, filter, etc)
	 *
	 * @param array $array
	 * @return array
	 */
	protected function applyDepthRestriction(array $array, $offset = 0)
	{
		return array_slice($array, 0, $this->getDepthRestriction() + $offset);
	}

	/**
	 * "Safe" version of with eager-loading.
	 *
	 * Checks if relations exist before loading them.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param string|array $relations
	 */
	protected function safeWith(Builder $query, $relations)
	{
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
			if (! $this->isIncludable($relation_name)) {
				unset($relations[$name]);
				continue;
			}

			// Expand the dot-notation to see all relations
			$nested_relations = explode(self::GLUE, $relation_name);
			$model = $query->getModel();

			// Don't allow eager loads beyond the eager load depth
			$nested_relations = $this->applyDepthRestriction($nested_relations);

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
	}

	/**
	 * Apply nested joins to allow nested sorting for select relationship combinations
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array $relations
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @param                                       $field
	 * @param string $direction
	 * @return void
	 */
	public function applyNestedJoins(Builder $query, array $relations, Model $instance, $field, $direction = 'asc')
	{
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
			$this->applyNestedJoins($query, $relations, $related->getModel(), $field, $direction);
		} else {
			$query->orderBy("$table.$field", $direction);
		}
	}

	/**
	 * Find an instance of a model by ID.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function find($id)
	{
		return $this->query()->find($id);
	}

	/**
	 * Find an instance of a model by ID, or fail.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function findOrFail($id): Model
	{
		return $this->query()->findOrFail($id);
	}

	/**
	 * Get all elements against the base query.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function all(): Collection
	{
		return $this->query()->get();
	}

	/**
	 * Return paginated response.
	 *
	 * @param  int $per_page
	 * @return \Illuminate\Contracts\Pagination\Paginator
	 */
	public function paginate($per_page): Paginator
	{
		return $this->query()->paginate($per_page);
	}

	/**
	 * Count all elements against the base query.
	 *
	 * @return int
	 */
	public function count(): int
	{
		return $this->query()->count();
	}

	/**
	 * Determine if the base query returns a nonzero count.
	 *
	 * @return bool
	 */
	public function hasAny(): bool
	{
		return $this->count() > 0;
	}

	/**
	 * Get a random value.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function random(): Model
	{
		return $this->query()->orderByRaw('RAND()')->first();
	}

	/**
	 * Get the primary key from input.
	 *
	 * @return mixed
	 */
	public function getInputId()
	{
		$input = $this->getInput();

		/** @var Model $model */
		$model = $this->getModelClass();

		// If the model or the input is not set, then we cannot get an id.
		if (! $model || ! $input) {
			return null;
		}

		return array_get($input, (new $model)->getKeyName());
	}

	/**
	 * Fill an instance of a model with all known fields.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @return mixed
	 * @todo support more relationship types, such as polymorphic ones!
	 */
	protected function fill(Model $instance): bool
	{
		$input = $this->getInput();
		$model_fields = $this->getFields($instance);
		$before_relations = [];
		$after_relations = [];
		$instance_model = get_class($instance);
		$safe_instance = new $instance_model;

		$input = ($safe_instance->getIncrementing()) ? array_except($input, [$instance->getKeyName()]) : $input;

		foreach ($input as $key => $value) {
			if (($relation = $this->isRelation($instance, $key, $instance_model)) && $this->isFillable($key)) {
				$relation_type = get_class($relation);

				switch ($relation_type) {
					case BelongsTo::class:
						$before_relations[] = [
							'relation' => $relation,
							'value' => $value,
						];
						break;
					case HasOne::class:
					case HasMany::class:
					case BelongsToMany::class:
						$after_relations[] = [
							'relation' => $relation,
							'value' => $value,
						];
						break;
				}
			} elseif ((in_array($key, $model_fields) || $instance->hasSetMutator($key)) && $this->isFillable($key)) {
				$instance->{$key} = $value;
			}
		}

		unset($safe_instance);

		$this->applyRelations($before_relations, $instance);
		$instance->save();
		$this->applyRelations($after_relations, $instance);

		return true;
	}

	/**
	 * Apply relations from an array to an instance model.
	 *
	 * @param array $specs
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @return void
	 */
	protected function applyRelations(array $specs, Model $instance)
	{
		foreach ($specs as $spec) {
			$this->cascadeRelation($spec['relation'], $spec['value'], $instance);
		}
	}

	/**
	 * Cascade relations through saves on a model.
	 *
	 * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @param array $input
	 * @param \Illuminate\Database\Eloquent\Model $parent
	 *
	 * @return void
	 */
	protected function cascadeRelation(Relation $relation, array $input, Model $parent = null)
	{
		// Make a child repository for containing the cascaded relationship through saves
		$target_model_class = get_class($relation->getQuery()->getModel());
		$relation_repository = (new self)->setModelClass($target_model_class);

		switch (get_class($relation)) {
			case BelongsTo::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $relation
				 */
				// For BelongsTo, simply associate by foreign key.
				// (We don't have to assume the parent model exists to do this.)
				$related = $relation_repository->setInput($input)->save();
				$relation->associate($related);
				break;
			case HasMany::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\HasMany $relation
				 */
				// The parent model "owns" child models; any not specified here should be deleted.
				$current_ids = $relation->pluck($this->getKeyName())->toArray();
				$new_ids = array_filter(array_column($input, $this->getKeyName()));
				$removed_ids = array_diff($current_ids, $new_ids);
				if ( !empty($removed_ids)) {
					$relation->whereIn($this->getKeyName(), $removed_ids)->delete();
				}

				// Set foreign keys on the children from the parent, and save.
				foreach ($input as $sub_input) {
					$sub_input[$relation->getPlainForeignKey()] = $parent->{$this->getKeyName()};
					$relation_repository->setInput($sub_input)->save();
				}
				break;
			case HasOne::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\HasOne $relation
				 */
				// The parent model "owns" the child model; if we have a new and/or different
				// existing child model, delete the old one.
				$current = $relation->getResults();
				if ( !is_null($current)
					&& ( !isset($input[$this->getKeyName()]) || $current->{$this->getKeyName()} !== intval($input[$this->getKeyName()]))
				) {
					$relation->delete();
				}

				// Set foreign key on the child from the parent, and save.
				$input[$relation->getPlainForeignKey()] = $parent->{$this->getKeyName()};
				$relation_repository->setInput($input)->save();
				break;
			case BelongsToMany::class:
				/**
				 * @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
				 */
				// Find all the IDs to sync.
				$ids = [];

				foreach ($input as $sub_input) {
					$id = $relation_repository->setInput($sub_input)->save()->{$this->getKeyName()};

					// If we were passed pivot data, pass it through accordingly.
					if (isset($sub_input['pivot'])) {
						$ids[$id] = (array)$sub_input['pivot'];
					} else {
						$ids[] = $id;
					}
				}

				// Sync to save pivot table and optional extra data.
				$relation->sync($ids);
				break;
		}
	}

	/**
	 * Create a model.
	 *
	 * @return Model | Collection
	 */
	public function create(): Model
	{
		$model_class = $this->getModelClass();
		$instance = new $model_class;
		$this->fill($instance);

		return $instance;
	}

	/**
	 * Create many models.
	 *
	 * @return Collection
	 */
	public function createMany(): Collection
	{
		$collection = new Collection();

		foreach ($this->getInput() as $item) {
			$repository = clone $this;
			$repository->setInput($item);
			$collection->add($repository->create());
		}

		return $collection;
	}

	/**
	 * Read a model.
	 *
	 * @param int|string|null $id
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function read($id = null): Model
	{
		return $this->findOrFail($id ?? $this->getInputId());
	}

	/**
	 * Update a model.
	 *
	 * @param int|string|null $id
	 *
	 * @return Model|Collection
	 */
	public function update($id = null): Model
	{
		$instance = $this->read($id);
		$this->fill($instance);

		return $this->read($instance->getKey());
	}

	/**
	 * Updates many models.
	 *
	 * @return Collection
	 */
	public function updateMany(): Collection
	{
		$collection = new Collection();

		foreach ($this->getInput() as $item) {
			$repository = clone $this;
			$repository->setInput($item);
			$collection->add($repository->update($repository->getInputId()));
		}

		return $collection;
	}

	/**
	 * Delete a model.
	 *
	 * @param int|string|null $id
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function delete($id = null): bool
	{
		$instance = $this->read($id);

		return $instance->delete();
	}

	/**
	 * Save a model, regardless of whether or not it is "new".
	 *
	 * @param int|string|null $id
	 *
	 * @return Model|Collection
	 */
	public function save($id = null): Model
	{
		$id = $id ?? $this->getInputId();

		if ($id) {
			return $this->update($id);
		}

		return $this->create();
	}

	/**
	 * Checks if the input has many items.
	 *
	 * @return bool
	 */
	public function isManyOperation(): bool
	{
		return ($this->getInput() && array_keys($this->getInput()) === range(0, count($this->getInput()) - 1));
	}
}