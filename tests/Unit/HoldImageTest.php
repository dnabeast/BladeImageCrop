<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\HoldImage;
use DNABeast\BladeImageCrop\View\Components\Img;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class HoldImageTest extends TestCase
{
	public function setUp() :void
	{
		parent::setUp();
		Config::set('bladeimagecrop',[
			'short_pixel_active' => env('BLADE_CROP_SHORTPIXEL', false),
			'disk' => 'public',
			'build_classes' => [
				'jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder',
				'webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder'
			]
		]);
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => storage_path('app/public'),
		]);

		$this->app->bind('path.public', function() {
			return str_replace("vendor/orchestra/testbench-core/laravel", "tests/Unit", base_path());
		});
	}

	/** @test */
	function give_it_a_local_image_that_exists_returns_path(){

		$file = 'uploads/banners/page/cater.jpg';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$file);

		Storage::fake('public');
		Storage::disk('public')->put($file, $image);

		$expected = 'blade_image_crop_holding/uploadsbannerspagecaterjpg.jpg';

		$holdImage = new HoldImage($file);

		$this->assertEquals(
			$expected,
			$holdImage->file()
		);

	}

	/** @test */
	function give_it_a_local_image_that_doesn_t_exists_returns_fail_message(){
		$file = 'uploads/banners/page/doesntexist.jpg';

		Storage::fake('public');

		$expected = 'FILE NOT FOUND';

		$holdImage = new HoldImage($file);

		$this->assertEquals(
			$expected,
			$holdImage->file()
		);
	}

	/** @test */
	function give_it_an_online_image_that_exists_returns_path(){
		$file = 'https://smartenough.org/img/stealbananas.jpg';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'uploads/banners/page/cater.jpg');

		Http::fake([$file => Http::response($image, 200) ]);

		Storage::fake('public');

		$expected = 'blade_image_crop_holding/httpssmartenoughorgimgstealbananasjpg.jpg';

		$holdImage = new HoldImage($file);

		$this->assertEquals(
			$expected,
			$holdImage->file()
		);
	}

	/** @test */
	function give_it_an_online_image_that_doesn_t_exists_returns_fail_message(){
		$file = 'https://smartenough.org/img/stealbananas.jpg';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'uploads/banners/page/cater.jpg');

		Http::fake();

		Storage::fake('public');

		$expected = 'blade_image_crop_holding/httpssmartenoughorgimgstealbananasjpg.jpg';

		$holdImage = new HoldImage($file);

		$this->assertEquals(
			$expected,
			$holdImage->file()
		);
	}

	// if the config option to compress the hold image is set the held image should be smaller than the original image
	/** @test  */
	function if_the_config_option_to_compress_the_hold_image_is_set_the_held_image_should_be_smaller_than_the_original_image(){
		Config::set(['bladeimagecrop.compress_held_image' => true]);

		$file = 'uploads/banners/page/shea.jpg';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$file);

		Storage::fake('public');
		Storage::disk('public')->put($file, $image);

		$originalSize = Storage::disk('public')->fileSize($file);

		$holdImage = (new HoldImage($file))->file();

		$heldSize = Storage::disk('public')->fileSize($holdImage);

		$this->assertLessThan(
			$originalSize,
			$heldSize
		);

	}
}
