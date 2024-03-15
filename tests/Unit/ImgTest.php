<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\View\Components\Img;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;



class ImgTest extends TestCase
{
	use InteractsWithViews;


	public function setUp() :void
	{
		parent::setUp();

		Blade::component('img', Img::class);
		Config::set('app.env', 'http://test.com');

		Config::set('bladeimagecrop', [
			'disk' => 'public',
			'images_from_public_path' => true,
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'],
			'backgrounds' => true,
			'text_labels' => env('BLADE_CROP_TEST_LABELS', false),
			'build_classes' => [
				'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_ShortPixelJPGBuilder',
				'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
			],
			'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'

		]);
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => __DIR__,
			'url' => 'http://test.com/public'

		]);

		$this->app->bind('path.public', function() {
			return str_replace("vendor/orchestra/testbench-core/laravel", "tests/Unit", base_path());
		});

		$this->base64 = "iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAMElEQVQImSXBwQ3AIBAEsZkVIf23Qje0ggKXB7ZzDBKAc4Kub6MmTX37A1DVKCiuH1lyC1aRmduXAAAAAElFTkSuQmCC";

	}


	/** @test */
	function give_x_img_component_and_return_filled_img_tag(){

		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  width="320"/>');

		$expectedBeginning = <<<EOT
		<img srcset="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg 1x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/640x480_50_50.jpg 2x" style="background-size: 100% 100%; background-image: url('data:image/png;base64,
		EOT;

		$expectedEnd = <<<EOT
		')" src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	/** @test */
	function give_x_img_component_with_1_prop_and_return_filled_img_tag(){

		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  :properties="[320]"/>');


		$expectedBeginning = <<<EOT
		<img srcset="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg 1x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/640x480_50_50.jpg 2x" style="background-size: 100% 100%; background-image: url('data:image/png;base64,
		EOT;

		$expectedEnd = <<<EOT
		')" src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	/** @test */
	function give_class_and_alt_tag_and_see_them_in_the_result(){
		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  width="320" class="m-1" alt="Description of Image"/>');

		$expectedBeginning = <<<EOT
		<img srcset="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg 1x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/640x480_50_50.jpg 2x" style="background-size: 100% 100%; background-image: url('data:image/png;base64,
		EOT;

		$expectedEnd = <<<EOT
		')" src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg" width="320" height="240" class="m-1" alt="Description of Image">
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	/** @test */
	function give_properties_and_see_them_in_the_result(){
		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png" :properties="[300, 200, 60]"  width="320" class="m-1" alt="Description of Image"/>');

		$expectedBeginning = <<<EOT
		<img srcset="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/300x200_60_50.jpg 1x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/600x400_60_50.jpg 2x" style="background-size: 100% 100%; background-image: url('data:image/png;base64,
		EOT;

		$expectedEnd = <<<EOT
		')" src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/300x200_60_50.jpg" width="300" height="200" class="m-1" alt="Description of Image">
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	/** @test */
	function set_changed_default_and_see_them_in_the_result(){
		Config::set('bladeimagecrop.offset_x', 40);
		Config::set('bladeimagecrop.offset_y', 70);
		Config::set('bladeimagecrop.pixel_device_ratios', ['1x', '2x', '4x']);

		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  width="320"/>');


		$expectedBeginning = <<<EOT
		<img srcset="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_40_70.jpg 1x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/640x480_40_70.jpg 2x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/1280x960_40_70.jpg 4x" style="background-size: 100% 100%; background-image: url('data:image/png;base64,
		EOT;

		$expectedEnd = <<<EOT
		')" src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_40_70.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	/** @test */
	function set_no_background_and_see_it_removed_from_the_result(){
		Config::set('bladeimagecrop.backgrounds', false);

		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  width="320"/>');

		$expected = <<<EOT
		<img srcset="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg 1x,/public/blade_image_crop_holding/imgoverlylargeimagepng_png/640x480_50_50.jpg 2x"  src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expected, false);
	}


	/** @test */
	function turn_sources_off_and_dont_see_srcset_at_all(){
		Config::set('bladeimagecrop.backgrounds', false);

		$result = $this->blade('<x-img sources="false" src="/img/OverlyLargeImage.png"  width="320"/>');

		$expected = <<<EOT
		<img   src="/public/blade_image_crop_holding/imgoverlylargeimagepng_png/320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expected, false);
	}

}
