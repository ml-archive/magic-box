<?php

namespace Fuzz\MagicBox;

use Fuzz\Data\Eloquent\Model;

class EloquentRepository
{
	/**
	 * An instance variable specifying the model handled by this repository.
	 *
	 * @var string
	 */
	private $model_class;

	/**
	 * Required input fields.
	 *
	 * @var array
	 */
	private $required_input_fields = [];

	/**
	 * An instance variable specifying the input passed to model instances.
	 *
	 * @var array
	 */
	private $input;

	/**
	 * Set the model for an instance of this resource controller.
	 *
	 * @param string $model_class
	 * @return static
	 */
	public function setModelClass($model_class)
	{
		if (! is_subclass_of($model_class, '\Fuzz\Data\Eloquent\Model')) {
			throw new \InvalidArgumentException('Specified model class mustb e an instance of \Fuzz\Data\Eloquent\Model');
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
	 * Set the required input fields.
	 *
	 * @param array $required_input_fields
	 * @return static
	 */
	public function setRequiredInputFields($required_input_fields)
	{
		$this->required_input_fields = $required_input_fields;

		return $this;
	}

	/**
	 * Get the required input fields.
	 *
	 * @return array
	 */
	public function getRequiredInputFields()
	{
		return $this->required_input_fields;
	}

	/**
	 * Set input manually.
	 *
	 * @param array $input
	 * @throws \ErrorException
	 * @return static
	 */
	public function setInput(array $input)
	{
		$missing_required = array_diff($this->getRequiredInputFields(), array_keys(array_filter($input)));

		if (count($missing_required) > 0) {
			throw new \InvalidArgumentException(sprintf('Missing required fields: [%s]', implode('], [', $missing_required)));
		}

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
	 * Base query for all behaviors within this repository.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function baseQuery()
	{
		return forward_static_call($this->getModelClass(), 'query');
	}

	/**
	 * Find an instance of a model by ID.
	 *
	 * @param int $id
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final protected function find($id)
	{
		return $this->baseQuery()->find($id);
	}

	/**
	 * Find an instance of a model by ID, or fail.
	 *
	 * @param int $id
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final protected function findOrFail($id)
	{
		return $this->baseQuery()->findOrFail($id);
	}

	/**
	 * Get the primary key from input.
	 *
	 * @return mixed
	 */
	final protected function getInputId()
	{
		$primary_key = forward_static_call($this->getModelClass(), 'newInstance')->getKeyName();
		$input       = $this->getInput();

		if (! array_key_exists($primary_key, $input)) {
			throw new \LogicException('ID is not specified in input.');
		}

		return $input[$primary_key];
	}

	/**
	 * Fill an instance of a model with all known fields.
	 *
	 * @param \Fuzz\Data\Eloquent\Model $instance
	 * @return mixed
	 */
	final protected function fill(Model $instance)
	{
		// @todo
	}

	/**
	 * Create a model.
	 *
	 * @return \Fuzz\Data\Eloquent\Model
	 */
	final public function create()
	{
		$instance = forward_static_call($this->getModelClass(), 'newInstance');
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
}
