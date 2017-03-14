<?php

namespace Fuzz\MagicBox\Contracts;

use Illuminate\Routing\Route;

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
