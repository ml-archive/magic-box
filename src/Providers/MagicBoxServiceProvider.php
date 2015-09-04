<?php

namespace Fuzz\Agency\Providers;


use Illuminate\Support\ServiceProvider;

/**
 * Class MagicBoxServiceProvider
 *
 * @package Fuzz\Agency\Providers
 */
class MagicBoxServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Publish config
		$this->publishes([__DIR__ . '/../config/magicbox.php' => config_path('magicbox.php')], 'config');

		parent::register();
	}
}
