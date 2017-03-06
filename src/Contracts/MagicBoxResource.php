<?php

namespace Fuzz\MagicBox\Contracts;

/**
 * Interface Resource
 *
 * @package Fuzz\Agency\Contracts
 */
interface MagicBoxResource
{
	/**
	 * Get the list of fields fillable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryFillable(): array;

	/**
	 * Get the list of relationships fillable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryIncludable(): array;

	/**
	 * Get the list of fields filterable by the repository
	 *
	 * @return array
	 */
	public function getRepositoryFilterable(): array;

}
