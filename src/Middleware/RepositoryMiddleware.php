<?php

namespace Fuzz\MagicBox\Middleware;

use Illuminate\Http\Request;
use Fuzz\MagicBox\Utility\Modeler;
use Illuminate\Support\Facades\Auth;
use Fuzz\MagicBox\EloquentRepository;
use Fuzz\MagicBox\Contracts\Repository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RepositoryMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
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
	 * @return \Fuzz\MagicBox\EloquentRepository
	 */
	public function buildRepository(Request $request)
	{
		$input = [];
		/** @var \Illuminate\Routing\Route $route */
		$route = $request->route();

		$modeler = new Modeler;

		if ($request->segment(2) === 'me') {
			/** @var \Illuminate\Database\Eloquent\Model $user */
			$user = Auth::user();

			if (is_null($user) || Auth::guest()) {
				throw new AccessDeniedHttpException('You must be logged in.');
			}

			$model_class = get_class($user);
			$input       = [$user->getKeyName() => $user->getKey()];
		} else {
			$model_class = $modeler->resolveModelClass($route);
		}

		// Look for /{model-class}/{id} RESTful requests
		$parameters = $route->parametersWithoutNulls();
		if (! empty($parameters)) {
			$id    = reset($parameters);
			$input = compact('id');
		}

		if ($request->method() !== 'GET') {
			$input += $request->all();
		}

		// Instantiate an eloquent repository bound to our standardized route parameter
		$repository = (new EloquentRepository)->setModelClass($model_class)
			->setFilters((array) $request->get('filters'))
			->setSortOrder((array) $request->get('sort'))
			->setEagerLoads((array) $request->get('include'))
			->setAggregate((array) $request->get('aggregate'))
			->setGroupBy((array) $request->get('group'))
			->setDepthRestriction(config('magicbox.depth_restriction', 3))
			->setInput($input);

		return $repository;
	}
}
