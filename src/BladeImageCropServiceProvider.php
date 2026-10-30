<?php

namespace DNABeast\BladeImageCrop;

use DNABeast\BladeImageCrop\Http\ImageController;
use DNABeast\BladeImageCrop\View\Components\Img;
use DNABeast\BladeImageCrop\View\Components\Pic;
use DNABeast\BladeImageCrop\View\Components\Sources;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Glide\ServerFactory;

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

		Route::get(config('bladeimagecrop.route_prefix') . '/{source}/{path}',
		ImageController::class)
			->where('source', implode('|', GlideUrls::SOURCES))
			->where('path', '.*')
			->name('bladeimagecrop.image');

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

		// Glide reads from a single filesystem, so each source gets its own server
		foreach (GlideUrls::SOURCES as $source) {
			$this->app->singleton('bladeimagecrop.server.' . $source, function () use ($source) {
				return ServerFactory::create([
					// A path string is read straight from that folder, no Laravel disk needed
					'source' => $source === 'public' ? public_path() : Storage::disk(config('bladeimagecrop.source_disk'))->getDriver(),
					'cache' => Storage::disk(config('bladeimagecrop.cache_disk'))->getDriver(),
					// Glide caches crops in a folder named after the source file (img/photo.jpg/abc.webp), which can't be made beside that file on a shared disk.
					// The source name keeps crops of the same path on different sources apart.
					'cache_path_prefix' => trim(config('bladeimagecrop.cache_path_prefix') . '/' . $source, '/'),
					'max_image_size' => config('bladeimagecrop.max_image_size'),
					'cache_with_file_extensions' => true,
				]);
			});
		}

	}
}
