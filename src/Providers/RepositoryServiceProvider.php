<?php

namespace Fuzz\MagicBox\Providers;

use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\MagicBox\EloquentRepository;
use Fuzz\MagicBox\Facades\ModelResolver;
use Fuzz\MagicBox\Utility\ExplicitModelResolver;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([$this->configPath() => config_path('magic-box.php')], 'config');
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		app()->singleton(Repository::class, function() {
			return new EloquentRepository;
		});

		app()->bind(ModelResolver::class, function() {
			return new ExplicitModelResolver;
		});
	}

	/**
	 * Get the config path
	 *
	 * @return string
	 */
	protected function configPath()
	{
		return realpath(__DIR__ . '/../../config/magic-box.php');
	}
}
