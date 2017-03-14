<?php

namespace Fuzz\MagicBox\Contracts;

use Fuzz\MagicBox\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

interface Repository
{
	/**
	 * Set the model for an instance of this resource controller.
	 *
	 * @param string $model_class
	 * @return static
	 */
	public function setModelClass($model_class);

	/**
	 * Get the model class.
	 *
	 * @return string
	 */
	public function getModelClass();

	/**
	 * Set input manually.
	 *
	 * @param array $input
	 * @return static
	 */
	public function setInput(array $input);

	/**
	 * Get input.
	 *
	 * @return array
	 */
	public function getInput();

	/**
	 * Get the PK name
	 *
	 * @return string
	 */
	public function getKeyName(): string;

	/**
	 * Determine if the model exists
	 *
	 * @return bool
	 */
	public function exists(): bool;

	/**
	 * Set eager load manually.
	 *
	 * @param array $eager_loads
	 * @return static
	 */
	public function setEagerLoads(array $eager_loads);

	/**
	 * Get eager loads.
	 *
	 * @return array
	 */
	public function getEagerLoads();

	/**
	 * Set filters manually.
	 *
	 * @param array $filters
	 * @return static
	 */
	public function setFilters(array $filters);

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function getFilters();

	/**
	 * Set modifiers.
	 *
	 * @param array $modifiers
	 * @return static
	 */
	public function setModifiers(array $modifiers);

	/**
	 * Get modifiers.
	 *
	 * @return array
	 */
	public function getModifiers();

	/**
	 * Return a model's fields.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @return array
	 */
	public static function getFields(Model $instance);

	/**
	 * Find an instance of a model by ID.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function find($id);

	/**
	 * Find an instance of a model by ID, or fail.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function findOrFail($id);

	/**
	 * Get all elements against the base query.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function all();

	/**
	 * Return paginated response.
	 *
	 * @param  int $per_page
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function paginate($per_page);

	/**
	 * Count all elements against the base query.
	 *
	 * @return int
	 */
	public function count();

	/**
	 * Determine if the base query returns a nonzero count.
	 *
	 * @return bool
	 */
	public function hasAny();

	/**
	 * Get a random value.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function random();

	/**
	 * Get the primary key from input.
	 *
	 * @return mixed
	 */
	public function getInputId();

	/**
	 * Create a model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function create();

	/**
	 * Read a model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function read();

	/**
	 * Update a model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function update();

	/**
	 * Update a model.
	 *
	 * @return boolean
	 */
	public function delete();

	/**
	 * Save a model, regardless of whether or not it is "new".
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function save();

	/**
	 * Set the fillable array
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function setFillable(array $fillable): EloquentRepository;

	/**
	 * Get the fillable attributes
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getFillable(bool $assoc = false): array;

	/**
	 * Add a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function addFillable(string $fillable): EloquentRepository;

	/**
	 * Add many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function addManyFillable(array $fillable): EloquentRepository;

	/**
	 * Remove a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function removeFillable(string $fillable): EloquentRepository;

	/**
	 * Remove many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function removeManyFillable(array $fillable): EloquentRepository;

	/**
	 * Determine whether a given key is fillable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFillable(string $key): bool;

	/**
	 * Set the relationships which can be included by the model
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function setIncludable(array $includable): EloquentRepository;

	/**
	 * Get the includable relationships
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getIncludable(bool $assoc = false): array;

	/**
	 * Add an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function addIncludable(string $includable): EloquentRepository;

	/**
	 * Add many includable fields
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function addManyIncludable(array $includable): EloquentRepository;

	/**
	 * Remove an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function removeIncludable(string $includable): EloquentRepository;

	/**
	 * Remove many includable relationships
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function removeManyIncludable(array $includable): EloquentRepository;

	/**
	 * Determine whether a given key is includable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isIncludable(string $key): bool;

	/**
	 * Set the fields which can be filtered on the model
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function setFilterable(array $filterable): EloquentRepository;

	/**
	 * Get the filterable fields
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getFilterable(bool $assoc = false): array;

	/**
	 * Add a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function addFilterable(string $filterable): EloquentRepository;

	/**
	 * Add many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function addManyFilterable(array $filterable): EloquentRepository;

	/**
	 * Remove a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function removeFilterable(string $filterable): EloquentRepository;

	/**
	 * Remove many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function removeManyFilterable(array $filterable): EloquentRepository;

	/**
	 * Determine whether a given key is filterable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFilterable(string $key): bool;
}
