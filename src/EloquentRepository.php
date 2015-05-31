<?php

namespace Fuzz\MagicBox;

use Fuzz\Data\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class EloquentRepository
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
	 * Set input manually.
	 *
	 * @param array $input
	 * @throws \ErrorException
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
	 * Base query for all behaviors within this repository.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function baseQuery()
	{
		return forward_static_call(
			[
				$this->getModelClass(),
				'query'
			]
		);
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
		$model_class   = $this->getModelClass();
		$temp_instance = new $model_class;
		$primary_key   = $temp_instance->getKeyName();
		unset($temp_instance);
		$input = $this->getInput();

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
	 * @todo support more relationship types, such as polymorphic ones!
	 */
	final protected function fill(Model $instance)
	{
		$input            = $this->getInput();
		$model_fields     = $instance->getFields();
		$before_relations = [];
		$after_relations  = [];

		foreach ($input as $key => $value) {
			if (method_exists($instance, $key)) {
				$relation = $instance->$key();
				if ($relation instanceof Relation) {
					$relation_type = get_class($relation);
					switch ($relation_type) {
						case 'Illuminate\Database\Eloquent\Relations\HasOne':
						case 'Illuminate\Database\Eloquent\Relations\HasMany':
						case 'Illuminate\Database\Eloquent\Relations\BelongsToMany':
							$after_relations[] = compact('relation_type', 'relation', 'value');
							break;
						case 'Illuminate\Database\Eloquent\Relations\BelongsTo':
							$before_relations[] = compact('relation_type', 'relation', 'value');
							break;
					}
				}
			} elseif (in_array($key, $model_fields) || $instance->hasSetMutator($key)) {
				$instance->$key = $value;
			}
		}

		foreach ($before_relations as $before_relation) {
			switch ($before_relation['relation_type']) {
				case 'Illuminate\Database\Eloquent\Relations\BelongsTo':
					$target_model_class        = get_class($before_relation['relation']->getQuery()->getModel());
					$model_resource_controller = new self;
					$related                   = $model_resource_controller->setModelClass($target_model_class)
						->setInput($before_relation['value'])->save();
					$before_relation['relation']->associate($related);
					break;
			}
		}

		$instance->save();

		foreach ($after_relations as $after_relation) {
			// … @todo
		}

		return true;
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
