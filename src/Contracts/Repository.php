<?php

namespace Fuzz\MagicBox\Contracts;

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
	 * Find an instance of a model by ID.
	 *
	 * @param int $id
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	public function find($id);

	/**
	 * Find an instance of a model by ID, or fail.
	 *
	 * @param int $id
	 * @return \Fuzz\Data\Eloquent\Model
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
	 * @return \Fuzz\Data\Eloquent\Model
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
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	public function create();

	/**
	 * Read a model.
	 *
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	public function read();

	/**
	 * Update a model.
	 *
	 * @return \Fuzz\Data\Eloquent\Model
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
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	public function save();
}
