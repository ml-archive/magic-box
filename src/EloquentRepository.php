<?php

namespace Fuzz\MagicBox;

use Fuzz\MagicBox\Utility\ChecksRelations;
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

class EloquentRepository implements Repository
{
    use ChecksRelations;

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
     * The key name used in all queries.
     *
     * @var int
     */
    const KEY_NAME = 'id';

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
     * @return static
     */
    public function setModelClass($model_class)
    {
        if (!is_subclass_of($model_class, Model::class)) {
            throw new \InvalidArgumentException('Specified model class must be an instance of ' . Model::class);
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
     * Get the eager load depth property.
     *
     * @return int
     */
    public function getDepthRestriction()
    {
        return $this->depth_restriction;
    }

    /**
     * Set the eager load depth property.
     * This will limit how deep relationships can be included.
     *
     * @param int $depth
     *
     * @return $this
     */
    public function setDepthRestriction($depth)
    {
        $this->depth_restriction = $depth;

        return $this;
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
     * Add filters to already existing filters without overwriting them.
     *
     * @param array $filters
     * @return static
     */
    public function addFilters(array $filters)
    {
        $this->filters = array_merge($this->filters, $filters);

        return $this;
    }

    /**
     * Add a single filter to already existing filters without overwriting them.
     *
     * @param string $key
     * @param string $value
     * @return static
     */
    public function addFilter(string $key, string $value)
    {
        $this->filters[$key] = $value;

        return $this;
    }


    /**
     * Get group by.
     *
     * @return array
     */
    public function getGroupBy()
    {
        return $this->group_by;
    }

    /**
     * Set group by manually.
     *
     * @param array $group_by
     *
     * @return $this
     */
    public function setGroupBy(array $group_by)
    {
        $this->group_by = $group_by;

        return $this;
    }

    /**
     * @return array
     */
    public function getAggregate()
    {
        return $this->aggregate;
    }

    /**
     * Set aggregate functions.
     *
     * @param array $aggregate
     *
     * @return $this
     */
    public function setAggregate(array $aggregate)
    {
        $this->aggregate = $aggregate;

        return $this;
    }

    /**
     * Set sort order manually.
     *
     * @param array $sort_order
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
     * Return a model's fields.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @return array
     */
    public static function getFields(Model $instance)
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

        if (!empty($eager_loads)) {
            $this->safeWith($query, $eager_loads);
        }

        if (!empty($modifiers = $this->getModifiers())) {
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
        $filters = $this->getFilters();
        $sort_order_options = $this->getSortOrder();
        $group_by = $this->getGroupBy();
        $aggregate = $this->getAggregate();

        // Check if filters or sorts are requested
        $filters_exist = !empty($filters);
        $sorts_exist = !empty($sort_order_options);
        $group_exist = !empty($group_by);
        $aggregate_exist = !empty($aggregate);

        // No modifications to apply
        if (!$filters_exist && !$sorts_exist && !$group_exist && !$aggregate_exist) {
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

        $safe_relations = [];
        // Loop through all relations to check for valid relationship signatures
        foreach ($relations as $name => $constraints) {
            // Constraints may be passed in either form:
            // 2 => 'relation.nested'
            // or
            // 'relation.nested' => function() { ... }
            $constraints_are_name = is_numeric($name);
            $relation_name = $constraints_are_name ? $constraints : $name;

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
    final public function find($id)
    {
        return $this->query()->find($id);
    }

    /**
     * Find an instance of a model by ID, or fail.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
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
     * @return \Illuminate\Database\Eloquent\Model
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
        if (!$this->exists()) {
            throw new \LogicException('ID is not specified in input.');
        }

        $input = $this->getInput();

        return $input[self::KEY_NAME];
    }

    /**
     * Fill an instance of a model with all known fields.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @return mixed
     * @todo support more relationship types, such as polymorphic ones!
     */
    final protected function fill(Model $instance)
    {
        $input = $this->getInput();
        $model_fields = $this->getFields($instance);
        $before_relations = [];
        $after_relations = [];
        $instance_model = get_class($instance);
        $safe_instance = new $instance_model;

        $fill_attributes = [];

        foreach (array_except($input, [$instance->getKeyName()]) as $key => $value) {
            if (($relation = $this->isRelation($instance, $key, $instance_model)) && $instance->isFillable($key)) {
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
            } elseif ((in_array($key, $model_fields) || $instance->hasSetMutator($key)) && $instance->isFillable($key)) {
                // Check for fillability status here so we don't throw a mass assignment exception
                // Any non-fillable fields simply won't be modified
                $fill_attributes[$key] = $value;
            }
        }

        unset($safe_instance);

        $this->applyRelations($before_relations, $instance);
        $instance->fill($fill_attributes)->save();
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
     * @param array $input
     * @param \Illuminate\Database\Eloquent\Model $parent
     *
     * @return void
     */
    final protected function cascadeRelation(Relation $relation, array $input, Model $parent = null)
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
                $current_ids = $relation->lists(self::KEY_NAME)->toArray();
                $new_ids = array_filter(array_column($input, self::KEY_NAME));
                $removed_ids = array_diff($current_ids, $new_ids);
                if (!empty($removed_ids)) {
                    $relation->whereIn(self::KEY_NAME, $removed_ids)->delete();
                }

                // Set foreign keys on the children from the parent, and save.
                foreach ($input as $sub_input) {
                    $sub_input[$relation->getPlainForeignKey()] = $parent->{self::KEY_NAME};
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
                if (!is_null($current)
                    && (!isset($input[self::KEY_NAME]) || $current->{self::KEY_NAME} !== intval($input[self::KEY_NAME]))
                ) {
                    $relation->delete();
                }

                // Set foreign key on the child from the parent, and save.
                $input[$relation->getPlainForeignKey()] = $parent->{self::KEY_NAME};
                $relation_repository->setInput($input)->save();
                break;
            case BelongsToMany::class:
                /**
                 * @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
                 */
                // Find all the IDs to sync.
                $ids = [];

                foreach ($input as $sub_input) {
                    $id = $relation_repository->setInput($sub_input)->save()->{self::KEY_NAME};

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
     * @return \Illuminate\Database\Eloquent\Model
     */
    final public function create()
    {
        $model_class = $this->getModelClass();
        $instance = new $model_class;
        $this->fill($instance);

        return $instance;
    }

    /**
     * Read a model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    final public function read()
    {
        return $this->findOrFail($this->getInputId());
    }

    /**
     * Update a model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    final public function update()
    {
        $instance = $this->read();
        $this->fill($instance);

        // Return the updated instance
        return $this->read();
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
     * @return \Illuminate\Database\Eloquent\Model
     */
    final public function save()
    {
        $input = $this->getInput();

        return isset($input['id']) ? $this->update() : $this->create();
    }
}
