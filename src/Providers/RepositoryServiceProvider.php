<?php

namespace Fuzz\MagicBox\Providers;

use Fuzz\MagicBox\Contracts\Repository;
use Fuzz\MagicBox\EloquentRepository;
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
		$this->publishes([$this->configPath() => config_path('magic-box.php')]);
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->publishes([$this->configPath() => config_path('magic-box.php')]);

		app()->instance(Repository::class, function () {
			return new EloquentRepository;
		});
	}

	/**
	 * Get the config path
	 *
	 * @return string
	 */
	protected function configPath()
	{
		return realpath(__DIR__ . '/../config/magic-box.php');
	}
}
