<?php

namespace Fuzz\MagicBox;

use Fuzz\Data\Eloquent\Model;
use Fuzz\MagicBox\Contracts\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class EloquentRepository implements Repository
{
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
	 * The key name used in all queries.
	 *
	 * @var int
	 */
	const KEY_NAME = 'id';

	/**
	 * Set the model for an instance of this resource controller.
	 *
	 * @param string $model_class
	 * @return static
	 */
	public function setModelClass($model_class)
	{
		if (! is_subclass_of($model_class, '\Fuzz\Data\Eloquent\Model')) {
			throw new \InvalidArgumentException('Specified model class must be an instance of \Fuzz\Data\Eloquent\Model');
		}

		$this->model_class = $model_class;

		return $this;
	}

	/**
	 * Get the model class.
	 *
	 * @return string
	 */
	public function getModelClass()
	{
		return $this->model_class;
	}

	/**
	 * Set input manually.
	 *
	 * @param array $input
	 * @return static
	 */
	public function setInput(array $input)
	{
		$this->input = $input;

		return $this;
	}

	/**
	 * Get input.
	 *
	 * @return array
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * Set eager load manually.
	 *
	 * @param array $eager_loads
	 * @return static
	 */
	public function setEagerLoads(array $eager_loads)
	{
		$this->eager_loads = $eager_loads;

		return $this;
	}

	/**
	 * Get eager loads.
	 *
	 * @return array
	 */
	public function getEagerLoads()
	{
		return $this->eager_loads;
	}

	/**
	 * Set filters manually.
	 *
	 * @param array $filters
	 * @return static
	 */
	public function setFilters(array $filters)
	{
		$this->filters = $filters;

		return $this;
	}

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * Set sort order manually.
	 *
	 * @param array $sort_orders
	 * @return $this
	 */
	public function setSortOrder(array $sort_order)
	{
		$this->sort_order = $sort_order;

		return $this;
	}

	/**
	 * Get sort order.
	 *
	 * @return array
	 */
	public function getSortOrder()
	{
		return $this->sort_order;
	}

	/**
	 * Set modifiers.
	 *
	 * @param array $modifiers
	 * @return static
	 */
	public function setModifiers(array $modifiers)
	{
		$this->modifiers = $modifiers;
	}

	/**
	 * Get modifiers.
	 *
	 * @return array
	 */
	public function getModifiers()
	{
		return $this->modifiers;
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
				'query'
			]
		);

		$this->modifyQuery($query);

		$eager_loads = $this->getEagerLoads();

		if (! empty($eager_loads)) {
			$query->safeWith($eager_loads);
		}

		if (! empty($modifiers = $this->getModifiers())) {
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
		$filters            = $this->getFilters();
		$sort_order_options = $this->getSortOrder();

		// Check if filters or sorts are requested
		$filters_exist = ! empty($filters);
		$sorts_exist   = ! empty($sort_order_options);

		// No modifications to apply
		if (! $filters_exist && ! $sorts_exist) {
			return;
		}

		// Make a mock instance so we can describe its columns
		$model_class   = $this->getModelClass();
		$temp_instance = new $model_class;
		$columns       = $temp_instance->getFields();
		//unset($temp_instance);

		if ($filters_exist) {
			$query->where(
				function ($query) use ($filters, $columns) {
					Filter::filterQuery($query, $filters, $columns);
				}
			);
		}

		if ($sorts_exist) {
			$allowed_directions = [
				'ASC',
				'DESC'
			];

			foreach ($sort_order_options as $order_by => $direction) {
				if (in_array(strtoupper($direction), $allowed_directions)) {
					$split = explode('.', $order_by);

					if (count($split) > 1) {
						// Pull out orderBy field
						$field      = array_pop($split);

						// Select only the base table fields, don't join relation data. Relations should be
						// explicitly included
						$base_table = $temp_instance->getTable();
						$query->selectRaw("$base_table.*");

						$this->applyNestedJoins($query, $split, $temp_instance, $field, $direction);
					} elseif (in_array($order_by, $columns)) {
						$query->orderBy($order_by, $direction);
					}
				}
			}
		}
	}

	/**
	 * Apply nested joins to allow nested sorting for select relationship combinations
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array                                 $relations
	 * @param \Fuzz\Data\Eloquent\Model             $instance
	 * @param                                       $field
	 * @param string                                $direction
	 * @return void
	 */
	public function applyNestedJoins(Builder $query, array $relations, Model $instance, $field, $direction = 'asc')
	{
		$base_table = $instance->getTable();

		// The current working relation
		$relation = $relations[0];

		// Current working table
		$table    = Str::plural($relation);
		$singular = Str::singular($relation);

		// If the relation exists, determine which type (singular, multiple)
		if (method_exists($instance, $singular)) {
			$related = $instance->$singular();
		} elseif (method_exists($instance, $relation)) {
			$related = $instance->$relation();
		} else {
			// This relation does not exist
			return;
		}

		$foreign_key = $related->getForeignKey();

		switch (class_basename($related)) {
			case 'BelongsToMany':
				$base_table_key = $instance->getKeyName();
				$relation_primary_key = $relation->getModel()->getKeyName();

				// Join through the pivot table
				$query->join($related->getTable(), "$base_table.$base_table_key", '=', $foreign_key);
				$query->join($table, $related->getOtherKey(), '=', "$relation.$relation_primary_key");
				break;
			case 'HasMany':
				$base_table_key = $instance->getKeyName();

				// Join child's table
				$query->join($table, "$base_table.$base_table_key", '=', $foreign_key);
				break;
			case 'BelongsTo':
			case 'HasOne':
				$relation_key = $related->getOtherKey();

				// Join related's table on the base table's foreign key
				$query->join($table, "$base_table.$foreign_key", '=', "$table.$relation_key");
				break;
		}

		// @todo is it necessary to allow nested relationships further than the first degree?
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
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function find($id)
	{
		return $this->query()->find($id);
	}

	/**
	 * Find an instance of a model by ID, or fail.
	 *
	 * @param int $id
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function findOrFail($id)
	{
		return $this->query()->findOrFail($id);
	}

	/**
	 * Get all elements against the base query.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	final public function all()
	{
		return $this->query()->get();
	}

	/**
	 * Return paginated response.
	 *
	 * @param  int $per_page
	 * @return \Illuminate\Pagination\Paginator
	 */
	final public function paginate($per_page)
	{
		return $this->query()->paginate($per_page);
	}

	/**
	 * Count all elements against the base query.
	 *
	 * @return int
	 */
	final public function count()
	{
		return $this->query()->count();
	}

	/**
	 * Determine if the base query returns a nonzero count.
	 *
	 * @return bool
	 */
	final public function hasAny()
	{
		return $this->count() > 0;
	}

	/**
	 * Get a random value.
	 *
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	public function random()
	{
		return $this->query()->orderByRaw('RAND()')->first();
	}

	/**
	 * Check if the model apparently exists.
	 *
	 * @return bool
	 */
	final public function exists()
	{
		return array_key_exists(self::KEY_NAME, $this->getInput());
	}

	/**
	 * Get the primary key from input.
	 *
	 * @return mixed
	 */
	final public function getInputId()
	{
		if (! $this->exists()) {
			throw new \LogicException('ID is not specified in input.');
		}

		$input = $this->getInput();

		return $input[self::KEY_NAME];
	}

	/**
	 * Fill an instance of a model with all known fields.
	 *
	 * @param \Fuzz\Data\Eloquent\Model $instance
	 * @return mixed
	 * @todo support more relationship types, such as polymorphic ones!
	 */
	final protected function fill(Model $instance)
	{
		$input            = $this->getInput();
		$model_fields     = $instance->getFields();
		$before_relations = [];
		$after_relations  = [];

		foreach (array_except($input, [$instance->getKeyName()]) as $key => $value) {
			if (method_exists($instance, $key)) {
				$relation = $instance->$key();
				if ($relation instanceof Relation) {
					$relation_type = class_basename($relation);
					switch ($relation_type) {
						case 'BelongsTo':
							$before_relations[] = compact('relation', 'value');
							break;
						case 'HasOne':
						case 'HasMany':
						case 'BelongsToMany':
							$after_relations[] = compact('relation', 'value');
							break;
					}
				}
			} elseif (in_array($key, $model_fields) || $instance->hasSetMutator($key)) {
				$instance->$key = $value;
			}
		}

		$this->applyRelations($before_relations, $instance);
		$instance->save();
		$this->applyRelations($after_relations, $instance);

		return true;
	}

	/**
	 * Apply relations from an array to an instance model.
	 *
	 * @param array                     $specs
	 * @param \Fuzz\Data\Eloquent\Model $instance
	 * @return void
	 */
	final protected function applyRelations(array $specs, Model $instance)
	{
		foreach ($specs as $spec) {
			$this->cascadeRelation($spec['relation'], $spec['value'], $instance);
		}
	}

	/**
	 * Cascade relations through saves on a model.
	 *
	 * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
	 * @param array                                            $input
	 * @param \Fuzz\Data\Eloquent\Model                        $parent
	 *
	 * @return void
	 */
	final protected function cascadeRelation(Relation $relation, array $input, Model $parent = null)
	{
		// Make a child repository for containing the cascaded relationship through saves
		$target_model_class        = get_class($relation->getQuery()->getModel());
		$model_resource_controller = (new self)->setModelClass($target_model_class);

		switch (class_basename($relation)) {
			case 'BelongsTo':
				// For BelongsTo, simply associate by foreign key.
				// (We don't have to assume the parent model exists to do this.)
				$related = $model_resource_controller->setInput($input)->save();
				$relation->associate($related);
				break;
			case 'HasMany':
				// The parent model "owns" child models; any not specified here should be deleted.
				$current_ids = $relation->lists(self::KEY_NAME)->toArray();
				$new_ids     = array_filter(array_column($input, self::KEY_NAME));
				$removed_ids = array_diff($current_ids, $new_ids);
				if (! empty($removed_ids)) {
					$relation->whereIn(self::KEY_NAME, $removed_ids)->delete();
				}

				// Set foreign keys on the children from the parent, and save.
				foreach ($input as $sub_input) {
					$sub_input[$relation->getPlainForeignKey()] = $parent->{self::KEY_NAME};
					$model_resource_controller->setInput($sub_input)->save();
				}
				break;
			case 'HasOne':
				// The parent model "owns" the child model; if we have a new and/or different
				// existing child model, delete the old one.
				$current = $relation->getResults();
				if (! is_null($current)
					&& (! isset($input[self::KEY_NAME]) || $current->{self::KEY_NAME} !== intval($input[self::KEY_NAME]))
				) {
					$relation->delete();
				}

				// Set foreign key on the child from the parent, and save.
				$input[$relation->getPlainForeignKey()] = $parent->{self::KEY_NAME};
				$model_resource_controller->setInput($input)->save();
				break;
			case 'BelongsToMany':
				// Find all the IDs to sync.
				$ids = [];

				foreach ($input as $sub_input) {
					$id = $model_resource_controller->setInput($sub_input)->save()->{self::KEY_NAME};

					// If we were passed pivot data, pass it through accordingly.
					if (isset($sub_input['pivot'])) {
						$ids[$id] = (array) $sub_input['pivot'];
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
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function create()
	{
		$model_class = $this->getModelClass();
		$instance    = new $model_class;
		$this->fill($instance);

		return $instance;
	}

	/**
	 * Read a model.
	 *
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function read()
	{
		return $this->findOrFail($this->getInputId());
	}

	/**
	 * Update a model.
	 *
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function update()
	{
		$instance = $this->read();
		$this->fill($instance);

		return $instance;
	}

	/**
	 * Update a model.
	 *
	 * @return boolean
	 */
	final public function delete()
	{
		$instance = $this->read();

		return $instance->delete();
	}

	/**
	 * Save a model, regardless of whether or not it is "new".
	 *
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function save()
	{
		$input = $this->getInput();

		return isset($input['id']) ? $this->update() : $this->create();
	}
}
