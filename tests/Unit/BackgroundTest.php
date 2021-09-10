<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\Background;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class BackgroundTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();

		Config::set('bladeimagecrop', [
			'disk' => 'public',
			'trim_directory' => 'storage',
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'],
			'text_labels' => env('BLADE_CROP_TEST_LABELS', false),
			'build_classes' => [
				'webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder',
				'jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
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
	function src_of_image_returns_svg_string(){
		$base64 = "iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAP0lEQVQImQE0AMv/ASsuCUorHmhoSfct/gT38//98f3p3PUFDQEDDg0HDAUBA/8A/f0UBAYDEA8aKuj/Egf3GCRIE53LsvHrAAAAAElFTkSuQmCC";

		$expected = 'style="background-size: 100% 100%; background-image: url(\'data:image/png;base64,'.$base64.'\')"';
		$src = 'uploads/banners/page/cater.jpg';

		$result = (new Background($src))->render();

		$this->assertEquals(
			$expected,
			$result
		);

	}
}