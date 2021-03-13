<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeImageCropServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 */
	public function boot()
	{
		/*
		 * Optional methods to load your package assets
		 */
		// $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'bladeimagecrop');
		// $this->loadViewsFrom(__DIR__.'/../resources/views', 'bladeimagecrop');
		// $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
		// $this->loadRoutesFrom(__DIR__.'/routes.php');

		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__.'/../config/config.php' => config_path('bladeimagecrop.php'),
			], 'config');

			// Publishing the views.
			/*$this->publishes([
				__DIR__.'/../resources/views' => resource_path('views/vendor/bladeimagecrop'),
			], 'views');*/

			// Publishing assets.
			/*$this->publishes([
				__DIR__.'/../resources/assets' => public_path('vendor/bladeimagecrop'),
			], 'assets');*/

			// Publishing the translation files.
			/*$this->publishes([
				__DIR__.'/../resources/lang' => resource_path('lang/vendor/bladeimagecrop'),
			], 'lang');*/

			// Registering package commands.
			// $this->commands([]);
		}
		Blade::directive('image', function($options){

			return "<?= app('DNABeast\BladeImageCrop\BladeImageCrop')->fire($options); ?>";
		});
	}

	/**
	 * Register the application services.
	 */
	public function register()
	{
		// Automatically apply the package configuration
		$this->mergeConfigFrom(__DIR__.'/../config/config.php', 'bladeimagecrop');

		// Register the main class to use with the facade
		$this->app->singleton('bladeimagecrop', function () {
			return new BladeImageCrop;
		});
	}
}
