<?php

namespace Fuzz\MagicBox;

use Closure;
use Fuzz\MagicBox\Contracts\AccessControl;
use Fuzz\MagicBox\Contracts\QueryModifier;
use Fuzz\MagicBox\Utility\ChecksRelations;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Fuzz\MagicBox\Contracts\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class EloquentAccessControl
 *
 * An AccessControl implementation for Eloquent.
 *
 * @package Fuzz\MagicBox
 */
class EloquentAccessControl implements AccessControl
{
	/**
	 * How many levels deep relationships can be included.
	 *
	 * @var int
	 */
	protected $depth_restriction = 0;

	/**
	 * Attributes on the model which can be modified by the repository
	 *
	 * @var array
	 */
	private $fillable = [];

	/**
	 * Relationships that can be included with the repository
	 *
	 * @var array
	 */
	private $includable = [];

	/**
	 * Fields that can be filtered on the repository
	 *
	 * @var array
	 */
	private $filterable = [];

	/**
	 * Get the eager load depth property.
	 *
	 * @return int
	 */
	public function getDepthRestriction(): int
	{
		return $this->depth_restriction;
	}

	/**
	 * Set the eager load depth property.
	 * This will limit how deep relationships can be included.
	 *
	 * @param int $depth
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setDepthRestriction($depth): AccessControl
	{
		$this->depth_restriction = $depth;

		return $this;
	}

	/**
	 * Apply a depth restriction to an exploded dot-nested string (eager load, filter, etc)
	 *
	 * @param array $array
	 * @param int   $offset
	 *
	 * @return array
	 */
	public function applyDepthRestriction(array $array, int $offset = 0)
	{
		return array_slice($array, 0, $this->getDepthRestriction() + $offset);
	}

	/**
	 * Set the fillable array
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setFillable(array $fillable): AccessControl
	{
		if ($this->canAllowAll($fillable)) {
			$this->fillable = self::ALLOW_ALL;

			return $this;
		}

		// Reset fillable
		$this->fillable = [];

		foreach ($fillable as $allowed_field) {
			$this->fillable[$allowed_field] = true;
		}

		return $this;
	}

	/**
	 * Get the fillable attributes
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getFillable(bool $assoc = false): array
	{
		if ($this->canAllowAll($this->fillable)) {
			return self::ALLOW_ALL;
		}

		return $assoc ? $this->fillable : array_keys($this->fillable);
	}

	/**
	 * Add a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addFillable(string $fillable): AccessControl
	{
		$this->fillable[$fillable] = true;

		return $this;
	}

	/**
	 * Add many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addManyFillable(array $fillable): AccessControl
	{
		foreach ($fillable as $allowed_field) {
			$this->addFillable($allowed_field);
		}

		return $this;
	}

	/**
	 * Remove a fillable attribute
	 *
	 * @param string $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeFillable(string $fillable): AccessControl
	{
		unset($this->fillable[$fillable]);

		return $this;
	}

	/**
	 * Remove many fillable fields
	 *
	 * @param array $fillable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeManyFillable(array $fillable): AccessControl
	{
		foreach ($fillable as $disallowed_field) {
			$this->removeFillable($disallowed_field);
		}

		return $this;
	}

	/**
	 * Determine whether a given key is fillable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFillable(string $key): bool
	{
		if ($this->canAllowAll($this->fillable)) {
			return true;
		}

		return isset($this->fillable[$key]) && $this->fillable[$key];
	}

	/**
	 * Set the relationships which can be included by the model
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setIncludable(array $includable): AccessControl
	{
		if ($this->canAllowAll($includable)) {
			$this->includable = self::ALLOW_ALL;

			return $this;
		}

		// Reset includable
		$this->includable = [];

		foreach ($includable as $allowed_include) {
			$this->includable[$allowed_include] = true;
		}

		return $this;
	}

	/**
	 * Get the includable relationships
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getIncludable(bool $assoc = false): array
	{
		if ($this->canAllowAll($this->includable)) {
			return self::ALLOW_ALL;
		}

		return $assoc ? $this->includable : array_keys($this->includable);
	}

	/**
	 * Add an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addIncludable(string $includable): AccessControl
	{
		$this->includable[$includable] = true;

		return $this;
	}

	/**
	 * Add many includable fields
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addManyIncludable(array $includable): AccessControl
	{
		foreach ($includable as $allowed_include) {
			$this->addIncludable($allowed_include);
		}

		return $this;
	}

	/**
	 * Remove an includable relationship
	 *
	 * @param string $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeIncludable(string $includable): AccessControl
	{
		unset($this->includable[$includable]);

		return $this;
	}

	/**
	 * Remove many includable relationships
	 *
	 * @param array $includable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeManyIncludable(array $includable): AccessControl
	{
		foreach ($includable as $disallowed_include) {
			$this->removeIncludable($disallowed_include);
		}

		return $this;
	}

	/**
	 * Determine whether a given key is includable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isIncludable(string $key): bool
	{
		if ($this->canAllowAll($this->includable)) {
			return true;
		}

		return isset($this->includable[$key]) && $this->includable[$key];
	}

	/**
	 * Set the fields which can be filtered on the model
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function setFilterable(array $filterable): AccessControl
	{
		if ($this->canAllowAll($filterable)) {
			$this->filterable = self::ALLOW_ALL;

			return $this;
		}

		// Reset filterable
		$this->filterable = [];

		foreach ($filterable as $allowed_field) {
			$this->filterable[$allowed_field] = true;
		}

		return $this;
	}

	/**
	 * Get the filterable fields
	 *
	 * @param bool $assoc
	 *
	 * @return array
	 */
	public function getFilterable(bool $assoc = false): array
	{
		if ($this->canAllowAll($this->filterable)) {
			return self::ALLOW_ALL;
		}

		return $assoc ? $this->filterable : array_keys($this->filterable);
	}

	/**
	 * Add a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addFilterable(string $filterable): AccessControl
	{
		$this->filterable[$filterable] = true;

		return $this;
	}

	/**
	 * Add many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function addManyFilterable(array $filterable): AccessControl
	{
		foreach ($filterable as $allowed_field) {
			$this->addFilterable($allowed_field);
		}

		return $this;
	}

	/**
	 * Remove a filterable field
	 *
	 * @param string $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeFilterable(string $filterable): AccessControl
	{
		unset($this->filterable[$filterable]);

		return $this;
	}

	/**
	 * Remove many filterable fields
	 *
	 * @param array $filterable
	 *
	 * @return \Fuzz\MagicBox\Contracts\AccessControl
	 */
	public function removeManyFilterable(array $filterable): AccessControl
	{
		foreach ($filterable as $disallowed_field) {
			$this->removeFilterable($disallowed_field);
		}

		return $this;
	}

	/**
	 * Determine whether a given key is filterable
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isFilterable(string $key): bool
	{
		if ($this->canAllowAll($this->filterable)) {
			return true;
		}

		return isset($this->filterable[$key]) && $this->filterable[$key];
	}

	/**
	 * Determine if the values are the ALLOW_ALL token
	 *
	 * @param array $values
	 *
	 * @return bool
	 */
	protected function canAllowAll(array $values): bool
	{
		return $values === AccessControl::ALLOW_ALL;
	}
}