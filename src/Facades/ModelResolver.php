<?php

namespace Fuzz\MagicBox\Facades;

use Illuminate\Support\Facades\Facade;

class ModelResolver extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	protected static function getFacadeAccessor()
	{
		return self::class;
	}
}
