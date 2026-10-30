<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\View\Components\Sources;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class SourcesTest extends TestCase
{
	use InteractsWithViews;

	public function setUp() :void
	{
		parent::setUp();

		Blade::component('sources', Sources::class);

		Config::set('bladeimagecrop', [
			'disk' => 'public',
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'], // add multipliers here for ultra high def screens
			'backgrounds' => true,
			'text_labels' => env('BLADE_CROP_TEST_LABELS', false), // These labels get written to the created images if they're not yet created.
			'build_classes' => [
				'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
				'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
			],
			'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
		]);
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => storage_path('app/public'),
			'url' => 'http://localhost'.'/storage',
			'visibility' => 'public',
			'throw' => false,
		]);
		Cache::set('bic_disk_url', 'http://localhost/');

		$this->app->bind('path.public', function() {
			return str_replace("vendor/orchestra/testbench-core/laravel", "tests/Unit", base_path());
		});
	}

	function test_if_blade_command_sources_requested_return_a_html_string(){

		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');
		Cache::set('bic_props_'.$url, [
			800,
			600,
		]);
		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');

		$storage->put('bic/img/OverlyLargeImage_png/bic_800x600_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_1600x1200_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_800x600_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_1600x1200_50_50.webp', $image);

		$expected = <<<EOT
		<source media="test" type="image/webp" srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_800x600_50_50.webp 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_1600x1200_50_50.webp 2x" sizes="sizeTest">
		<source media="test" type="image/jpeg" srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_800x600_50_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_1600x1200_50_50.jpg 2x" sizes="sizeTest">
		EOT;

		$result = $this->blade('<x-sources src="img/OverlyLargeImage.png" :properties="[800, 600]" media="test" sizes="sizeTest"/>');

		$result->assertSee($expected, false );

	}

	function test_if_blade_command_sources_requested_with_many_properties_return_a_html_string(){
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');
		Cache::set('bic_props_'.$url, [
			800,
			600,
		]);
		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');

		$storage->put('bic/img/OverlyLargeImage_png/bic_800x600_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_1024x768_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_800x600_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_1024x768_50_50.webp', $image);

		$expected = <<<EOT
		<source media="test" type="image/webp" srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_800x600_50_50.webp 800w,http://localhost/bic/img/OverlyLargeImage_png/bic_1024x768_50_50.webp 1024w" sizes="sizeTest">
		<source media="test" type="image/jpeg" srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_800x600_50_50.jpg 800w,http://localhost/bic/img/OverlyLargeImage_png/bic_1024x768_50_50.jpg 1024w" sizes="sizeTest">
		EOT;

		$result = $this->blade('<x-sources src="img/OverlyLargeImage_png" :properties="[[800, 600], 1024]" media="test" sizes="sizeTest"/>');

		$result->assertSee($expected, false );

	}

	// assert contains only instances of

	function test_if_properties_is_not_dynamic_throw_exception(){

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Properties must use : as a prefix.');

		$result = $this->blade('<x-sources src="uploads/banners/page/cater.jpg" properties="[800, 600]"/>');


	}

}