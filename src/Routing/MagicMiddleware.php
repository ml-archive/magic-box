<?php

namespace Fuzz\MagicBox\Routing;

use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\MagicBox\Utility\ModelResolver;
use Illuminate\Http\Request;

/**
 * Class MagicMiddleware
 *
 * @package Fuzz\MagicBox\Routing
 */
class MagicMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
	 *
	 * @return mixed
	 */
	public function handle(Request $request, \Closure $next)
	{
		// Bind the repository contract to this concrete instance so it can be injected in resource routes
		app()->instance(Repository::class, $this->buildRepository($request));

		return $next($request);
	}

	/**
	 * Build a repository based on inbound request data.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository;
	 */
	public function buildRepository(Request $request)
	{
		$input = [];

		/** @var \Illuminate\Routing\Route $route */
		$route = $request->route();

		// Look for /{model-class}/{id} RESTful requests
		// @TODO this is weird... Need to figure something out better.
		$parameters = $route->parametersWithoutNulls();
		if (!empty($parameters)) {
			$id = reset($parameters);
			$input = compact('id');
		}

		if ($request->method() !== 'GET') {
			$input += $request->all();
		}

		// Resolve the model class if possible. And setup the repository.
		$model_class = (new ModelResolver())->resolveModelClass($route);

		/** @var  \Fuzz\MagicBox\Contracts\Repository */
		$repository = config('magicbox.repository');

		// Instantiate an eloquent repository bound to our standardized route parameter
		$magicBox = (new $repository)->setModelClass($model_class)
			->setFilters((array)$request->get('filters'))
			->setSortOrder((array)$request->get('sort'))
			->setGroupBy((array)$request->get('group'))
			->setEagerLoads((array)$request->get('include'))
			->setAggregate((array)$request->get('aggregate'))
			->setInput($input);

		return $magicBox;
	}
}
