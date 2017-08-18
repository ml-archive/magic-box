<?php

namespace Fuzz\MagicBox\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\Paginator;

interface Repository
{
	/**
	 * Set the model for an instance of this resource controller.
	 *
	 * @param string $model_class
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setModelClass($model_class): Repository;

	/**
	 * Get the model class.
	 *
	 * @return string
	 */
	public function getModelClass(): string;

	/**
	 * Set input manually.
	 *
	 * @param array $input
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setInput(array $input): Repository;

	/**
	 * Get input.
	 *
	 * @return array
	 */
	public function getInput(): array;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setEagerLoads(array $eager_loads): Repository;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setFilters(array $filters): Repository;

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function getFilters(): array;

	/**
	 * Add a single modifier
	 *
	 * @param \Closure $modifier
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addModifier(Closure $modifier): Repository;

	/**
	 * Set modifiers.
	 *
	 * @param array $modifiers
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setModifiers(array $modifiers): Repository;

	/**
	 * Get modifiers.
	 *
	 * @return array
	 */
	public function getModifiers(): array;

	/**
	 * Return a model's fields.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $instance
	 * @return array
	 */
	public static function getFields(Model $instance): array;

	/**
	 * Find an instance of a model by ID.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model|null
	 */
	public function find($id);

	/**
	 * Find an instance of a model by ID, or fail.
	 *
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function findOrFail($id): Model;

	/**
	 * Get all elements against the base query.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function all(): Collection;

	/**
	 * Return paginated response.
	 *
	 * @param  int $per_page
	 * @return \Illuminate\Contracts\Pagination\Paginator
	 */
	public function paginate($per_page): Paginator;

	/**
	 * Count all elements against the base query.
	 *
	 * @return int
	 */
	public function count(): int;

	/**
	 * Determine if the base query returns a nonzero count.
	 *
	 * @return bool
	 */
	public function hasAny(): bool;

	/**
	 * Get a random value.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function random(): Model;

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
	public function create(): Model;

	/**
	 * Read a model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function read(): Model;

	/**
	 * Update a model.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function update(): Model;

	/**
	 * Update a model.
	 *
	 * @return boolean
	 */
	public function delete(): bool;

	/**
	 * Save a model, regardless of whether or not it is "new".
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function save(): Model;

	/**
	 * Set the fillable array
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setFillable(array $fillable): Repository;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addFillable(string $fillable): Repository;

	/**
	 * Add many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addManyFillable(array $fillable): Repository;

	/**
	 * Remove a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeFillable(string $fillable): Repository;

	/**
	 * Remove many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeManyFillable(array $fillable): Repository;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setIncludable(array $includable): Repository;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addIncludable(string $includable): Repository;

	/**
	 * Add many includable fields
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addManyIncludable(array $includable): Repository;

	/**
	 * Remove an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeIncludable(string $includable): Repository;

	/**
	 * Remove many includable relationships
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeManyIncludable(array $includable): Repository;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function setFilterable(array $filterable): Repository;

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
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addFilterable(string $filterable): Repository;

	/**
	 * Add many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function addManyFilterable(array $filterable): Repository;

	/**
	 * Remove a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeFilterable(string $filterable): Repository;

	/**
	 * Remove many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository
	 */
	public function removeManyFilterable(array $filterable): Repository;

	/**
	 * Determine whether a given key is filterable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFilterable(string $key): bool;
}
