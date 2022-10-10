<?php

namespace Fuzz\MagicBox\Utility;

use Fuzz\MagicBox\Contracts\ModelResolver;
use Fuzz\MagicBox\Exception\ModelNotResolvedException;
use Illuminate\Routing\Route;

/**
 * Class ExplicitModelResolver
 *
 * A ModelResolver which expects a controller to define the resource its repositories should work on.
 *
 * @package Fuzz\MagicBox\Utility
 */
class ExplicitModelResolver implements ModelResolver
{
	/**
	 * Resolve and return the model class for requests.
	 *
	 * @param \Illuminate\Routing\Route $route
	 *
	 * @return string
	 * @throws \Fuzz\MagicBox\Exception\ModelNotResolvedException
	 *
	 * @TODO Add check on route groups properties so resource settings can be set in groups.
	 * @TODO But make sure it still follows the rule of most specificity.
	 * (In order from least to most specific) Controller -> Route Group -> Route.
	 */
	public function resolveModelClass(Route $route): string
	{
		// If the route has a resource property we can instantly resolve the model.
		if ($this->routeHasResource($route)) {
			return $this->getRouteResource($route);
		}

		// If the action is a Closure instance, and the route does not have
		// a resource property then we can not resolve to a model.
		if ($this->actionIsCallable($route->getAction())) {
			// Model could not be resolved
			throw new ModelNotResolvedException;
		}

		// If the routes controller has a resource set then return that resource.
		if ($this->controllerHasResource($controller = $this->getRouteController($route))) {
			return $this->getControllerResource($controller);
		}

		// Model could not be resolved
		throw new ModelNotResolvedException;
	}

	/**
	 * Checks the route for a resource property.
	 *
	 * @param \Illuminate\Routing\Route $route
	 *
	 * @return bool
	 */
	public function routeHasResource(Route $route)
	{
		return array_key_exists('resource', $route->getAction());
	}

	/**
	 * Get the resource property from a route.
	 *
	 * @param \Illuminate\Routing\Route $route
	 *
	 * @return string
	 */
	public function getRouteResource(Route $route)
	{
		return $route->getAction()['resource'];
	}

	/**
	 * Checks if the action uses a callable.
	 *
	 * @param $action
	 *
	 * @return bool
	 */
	public function actionIsCallable($action)
	{
		return (is_callable($action['uses']));
	}

	/**
	 * Check if the controller has a resource.
	 *
	 * @param $controller
	 *
	 * @return bool
	 */
	public function controllerHasResource($controller)
	{
		return isset($controller::$resource);
	}

	/**
	 * Get the routes controller.
	 *
	 * @param \Illuminate\Routing\Route $route
	 *
	 * @return string
	 */
	public function getRouteController(Route $route)
	{
		return explode('@', $route->getAction()['uses'])[0];
	}

	/**
	 * Get the controllers resource.
	 *
	 * @param $controller
	 *
	 * @return mixed
	 */
	public function getControllerResource($controller)
	{
		return $controller::$resource;
	}

	/**
	 * Get the routes method.
	 *
	 * @param \Illuminate\Routing\Route $route
	 *
	 * @return mixed
	 */
	public function getRouteMethod(Route $route)
	{
		return explode('@', $route->getAction()['uses'])[1];
	}
}
