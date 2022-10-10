<?php

namespace Fuzz\MagicBox\Contracts;

/**
 * Interface AccessControl
 *
 * An AccessControl defines access to a resource via a Repository. Its responsible for determining
 * depth restrictions, allowed includes, allowed filters, etc.
 *
 * @package Fuzz\MagicBox\Contracts
 */
interface AccessControl
{
	/**
	 * Value that defines allowing all fields/relationships in includable/filterable/fillable
	 *
	 * @const array
	 */
	const ALLOW_ALL = ['*'];

	/**
	 * Get the eager load depth property.
	 *
	 * @return int
	 */
	public function getDepthRestriction();

	/**
	 * Set the eager load depth property.
	 * This will limit how deep relationships can be included.
	 *
	 * @param int $depth
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setDepthRestriction($depth);

	/**
	 * Apply a depth restriction to an exploded dot-nested string (eager load, filter, etc)
	 *
	 * @param array $array
	 * @param int   $offset
	 *
	 * @return array
	 */
	public function applyDepthRestriction(array $array, int $offset = 0);

	/**
	 * Set the fillable array
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setFillable(array $fillable): AccessControl;

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
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addFillable(string $fillable): AccessControl;

	/**
	 * Add many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addManyFillable(array $fillable): AccessControl;

	/**
	 * Remove a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeFillable(string $fillable): AccessControl;

	/**
	 * Remove many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeManyFillable(array $fillable): AccessControl;

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
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setIncludable(array $includable): AccessControl;

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
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addIncludable(string $includable): AccessControl;

	/**
	 * Add many includable fields
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addManyIncludable(array $includable): AccessControl;

	/**
	 * Remove an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeIncludable(string $includable): AccessControl;

	/**
	 * Remove many includable relationships
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeManyIncludable(array $includable): AccessControl;

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
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setFilterable(array $filterable): AccessControl;

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
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addFilterable(string $filterable): AccessControl;

	/**
	 * Add many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addManyFilterable(array $filterable): AccessControl;

	/**
	 * Remove a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeFilterable(string $filterable): AccessControl;

	/**
	 * Remove many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeManyFilterable(array $filterable): AccessControl;

	/**
	 * Determine whether a given key is filterable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFilterable(string $key): bool;
}