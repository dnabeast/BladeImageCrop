<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\View\Components\Img;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class ImgTest extends TestCase
{
	use InteractsWithViews;


	public function setUp() :void
	{
		parent::setUp();

		Blade::component('img', Img::class);
		Config::set('app.env', 'http://localhost.com');

		Config::set('bladeimagecrop', [
			'disk' => 'public',
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
			'root' => storage_path('app/public'),
			'url' => 'http://localhost'.'/storage',
			'visibility' => 'public',
			'throw' => false,
		]);

	Cache::set('bic_disk_url', 'http://localhost/');

		$this->app->bind('path.public', function() {
			return str_replace("vendor/orchestra/testbench-core/laravel", "tests/Unit", base_path());
		});

		$this->base64 = "iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAMElEQVQImSXBwQ3AIBAEsZkVIf23Qje0ggKXB7ZzDBKAc4Kub6MmTX37A1DVKCiuH1lyC1aRmduXAAAAAElFTkSuQmCC";

	}

	function test_give_x_img_component_and_return_filled_img_tag(){

		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');

		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);

		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  width="320"/>');

		$expectedBeginning = <<<EOT
		<img srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg 2x" style="background-size: 100% 100%; background-image:
		EOT;

		$expectedEnd = <<<EOT
		')" src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	function test_give_x_img_component_with_1_prop_and_return_filled_img_tag(){

		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);


		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  :properties="[320]"/>');

		$expectedBeginning = <<<EOT
		<img srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg 2x" style="background-size: 100% 100%; background-image:
		EOT;

		$expectedEnd = <<<EOT
		')" src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	function test_give_class_and_alt_tag_and_see_them_in_the_result(){
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);


		$result = $this->blade('<x-img src="/img/OverlyLargeImage.png"  width="320" class="m-1" alt="Description of Image"/>');

		$expectedBeginning = <<<EOT
		<img srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg 2x" style="background-size: 100% 100%; background-image:
		EOT;

		$expectedEnd = <<<EOT
		')" src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg" width="320" height="240" class="m-1" alt="Description of Image">
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	function test_give_properties_and_see_them_in_the_result(){
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_300x200_60_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_600x400_60_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_300x200_60_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_600x400_60_50.webp', $image);


		$result = $this->blade('<x-img src="/img/OverlyLargeImage.png" :properties="[300, 200, 60]"  width="320" class="m-1" alt="Description of Image"/>');

		$expectedBeginning = <<<EOT
		<img srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_300x200_60_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_600x400_60_50.jpg 2x" style="background-size: 100% 100%; background-image:
		EOT;

		$expectedEnd = <<<EOT
		')" src="http://localhost/bic/img/OverlyLargeImage_png/bic_300x200_60_50.jpg" width="300" height="200" class="m-1" alt="Description of Image">
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	function test_set_changed_default_and_see_them_in_the_result(){
		Config::set('bladeimagecrop.offset_x', 40);
		Config::set('bladeimagecrop.offset_y', 70);
		Config::set('bladeimagecrop.pixel_device_ratios', ['1x', '2x', '4x']);

		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_40_70.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_40_70.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_1280x960_40_70.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_40_70.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_40_70.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_1280x960_40_70.webp', $image);


		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png" width="320"/>');


		$expectedBeginning = <<<EOT
		<img srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_40_70.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_40_70.jpg 2x,http://localhost/bic/img/OverlyLargeImage_png/bic_1280x960_40_70.jpg 4x" style="background-size: 100% 100%; background-image:
		EOT;

		$expectedEnd = <<<EOT
		')" src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_40_70.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);
	}

	function test_set_no_background_and_see_it_removed_from_the_result(){
		Config::set('bladeimagecrop.backgrounds', false);
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);

		$result = $this->blade('<x-img  src="/img/OverlyLargeImage.png"  width="320"/>');

		$expected = <<<EOT
		<img srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg 2x"  src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expected, false);
	}


	function test_turn_sources_off_and_dont_see_srcset_at_all(){
		Config::set('bladeimagecrop.backgrounds', false);
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);

		$result = $this->blade('<x-img sources="false" src="/img/OverlyLargeImage.png"  width="320"/>');

		$expected = <<<EOT
		<img   src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg" width="320" height="240" >
		EOT;

		$result->assertSee($expected, false);
	}

	function test_set_bic_to_not_enabled_and_return_with_width_and_height(){
		Config::set('bladeimagecrop.enabled', false);
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);

		$result = $this->blade('<x-img src="/img/OverlyLargeImage.png"  width="320"/>');

		$expected = <<<EOT
		<img style="object-fit: cover; width: 320px; height: auto;" src="/img/OverlyLargeImage.png" width="320px" height="auto">
		EOT;

		$result->assertSee($expected, false);
	}

	function test_set_bic_to_not_enabled_and_return_with_properties_width_and_height(){
		Config::set('bladeimagecrop.enabled', false);
		$url = config('app.env').'/img/OverlyLargeImage.png';
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'img/OverlyLargeImage.png');

		Http::fake([
			$url => $image,
		]);

		$storage = Storage::fake('public');
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp', $image);
		$storage->put('bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp', $image);

		$result = $this->blade('<x-img src="/img/OverlyLargeImage.png" :properties="[320]"/>');

		$expected = <<<EOT
		<img style="object-fit: cover; width: 320px; height: auto;" src="/img/OverlyLargeImage.png" width="320px" height="auto">
		EOT;

		$result->assertSee($expected, false);
	}


}
