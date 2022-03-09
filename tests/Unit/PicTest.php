<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\View\Components\Img;
use DNABeast\BladeImageCrop\View\Components\Pic;
use DNABeast\BladeImageCrop\View\Components\Sources;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
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
			'trim_directory' => 'storage',
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'],
			'backgrounds' => true,
			'text_labels' => env('BLADE_CROP_TEST_LABELS', false),
			'build_classes' => [
				'webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
				'jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder',
			],
			'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'

		]);
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => $path,
		]);


	}

	/** @test */
	function provide_pic_tag_and_get_html(){
		$result = $this->blade('<x-pic  src="/img/OverlyLargeImage.png"  width="320" class="m-1" alt="Description of Image"/>');
		$base64 = "iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAMElEQVQImSXBwQ3AIBAEsZkVIf23Qje0ggKXB7ZzDBKAc4Kub6MmTX37A1DVKCiuH1lyC1aRmduXAAAAAElFTkSuQmCC";

		$expected = <<<EOT
		<source type="image/webp" srcset="/img/OverlyLargeImage_png/320x240_50_50.webp 1x,/img/OverlyLargeImage_png/640x480_50_50.webp 2x">
		<source type="image/jpeg" srcset="/img/OverlyLargeImage_png/320x240_50_50.jpg 1x,/img/OverlyLargeImage_png/640x480_50_50.jpg 2x">	<img  style="background-size: 100% 100%; background-image: url('data:image/png;base64,{$base64}')" src="/img/OverlyLargeImage_png/320x240_50_50.jpg" width="320" height="240" class="m-1" alt="Description of Image">
		EOT;

		$result->assertSee($expected, false);

	}


}