<?php

namespace Fuzz\MagicBox\Utility;


trait MagicBoxEloquentResource
{
	/**
	 * The relationships that can be included.
	 *
	 * @var array
	 */
	protected $includable = [];

	/**
	 * The attributes that can be used to filter.
	 *
	 * @var array
	 */
	protected $filterable = [];

	/**
	 * Get the list of fields fillable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryFillable(): array
	{
		return $this->getFillable();
	}

	/**
	 * Get the list of relationships fillable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryIncludable(): array
	{
		return $this->includable;
	}

	/**
	 * Get the list of fields filterable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryFilterable(): array
	{
		return $this->filterable;
	}
}