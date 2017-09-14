<?php

namespace Fuzz\MagicBox\Contracts;

use Illuminate\Routing\Route;

/**
 * Interface ModelResolver
 *
 * A ModelResolver determines which MagicBoxResource is being worked on via a Route.
 *
 * @package Fuzz\MagicBox\Contracts
 */
interface ModelResolver
{
	/**
	 * Resolve and return the model class for requests.
	 *
	 * @param \Illuminate\Routing\Route $route
	 * @return string
	 */
	public function resolveModelClass(Route $route): string;
}
