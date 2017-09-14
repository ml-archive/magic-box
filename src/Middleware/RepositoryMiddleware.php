<?php

namespace Fuzz\MagicBox\Middleware;

use Fuzz\MagicBox\Facades\ModelResolver;
use Illuminate\Http\Request;
use Fuzz\MagicBox\EloquentRepository;
use Fuzz\MagicBox\Contracts\Repository;

/**
 * Class RepositoryMiddleware
 *
 * Responsible for accepting a request and building a Repository for the appropriate class.
 *
 * @package Fuzz\MagicBox\Middleware
 */
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
		$this->buildRepository($request);

		return $next($request);
	}

	/**
	 * Build a repository based on inbound request data.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return \Fuzz\MagicBox\Contracts\Repository|\Fuzz\MagicBox\EloquentRepository
	 */
	public function buildRepository(Request $request): EloquentRepository
	{
		$input = [];

		/** @var \Illuminate\Routing\Route $route */
		$route = $request->route();

		// Resolve the model class if possible. And setup the repository.
		$model_class = ModelResolver::resolveModelClass($route);

		// Look for /{model-class}/{id} RESTful requests
		$parameters = $route->parametersWithoutNulls();
		if (! empty($parameters)) {
			$id    = reset($parameters);
			$input = compact('id');
		}

		// If the method is not GET lets get the input from everywhere.
		if ($request->method() !== 'GET') {
			$input += $request->all();
		}

		// Resolve an eloquent repository bound to our standardized route parameter
		/** @var Repository $repository */
		$repository = resolve(Repository::class);

		$repository->setModelClass($model_class)->setInput($input);

		$repository->modify()
			->setFilters((array) $request->get('filters'))
			->setSortOrder((array) $request->get('sort'))
			->setGroupBy((array) $request->get('group'))
			->setEagerLoads((array) $request->get('include'))
			->setAggregate((array) $request->get('aggregate'));

		$repository->accessControl()->setDepthRestriction(config('magic-box.eager_load_depth'));

		return $repository;
	}
}
