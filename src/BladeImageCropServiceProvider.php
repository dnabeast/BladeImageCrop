<?php

namespace DNABeast\BladeImageCrop;

use DNABeast\BladeImageCrop\View\Components\Img;
use DNABeast\BladeImageCrop\View\Components\Pic;
use DNABeast\BladeImageCrop\View\Components\Sources;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeImageCropServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 */
	public function boot()
	{

		Blade::component('sources', Sources::class);
		Blade::component('img', Img::class);
		Blade::component('pic', Pic::class);

		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__.'/../config/bladeimagecrop.php' => config_path('bladeimagecrop.php'),
			], 'config');
		}

	}

	/**
	 * Register the application services.
	 */
	public function register()
	{
		// Automatically apply the package configuration
		$this->mergeConfigFrom(__DIR__.'/../config/bladeimagecrop.php', 'bladeimagecrop');

	}
}
