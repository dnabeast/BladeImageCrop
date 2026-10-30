<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\View\Components\Img;
use DNABeast\BladeImageCrop\View\Components\Pic;
use DNABeast\BladeImageCrop\View\Components\Sources;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class PicTest extends TestCase
{
	use InteractsWithViews;

	public function setUp() :void
	{
		parent::setUp();

		Blade::component('pic', Pic::class);
		Blade::component('img', Img::class);
		Blade::component('sources', Sources::class);

		Config::set('bladeimagecrop', [
			'disk' => 'public',
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'],
			'backgrounds' => true,
			'build_classes' => [
				'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
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

	}

	function test_provide_pic_tag_and_get_html(){

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


		$result = $this->blade('<x-pic  src="/img/OverlyLargeImage.png"  width="320" class="m-1" alt="Description of Image"/>');

		$expectedBeginning = <<<EOT
		<source type="image/webp" srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.webp 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_50_50.webp 2x">
		<source type="image/jpeg" srcset="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg 1x,http://localhost/bic/img/OverlyLargeImage_png/bic_640x480_50_50.jpg 2x"><img  style="background-size: 100% 100%; background-image:
		EOT;

		$expectedEnd = <<<EOT
		')" src="http://localhost/bic/img/OverlyLargeImage_png/bic_320x240_50_50.jpg" width="320" height="240" class="m-1" alt="Description of Image">
		EOT;

		$result->assertSee($expectedBeginning, false);
		$result->assertSee($expectedEnd, false);

	}


}