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
				'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder',
				'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
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
	function src_of_image_returns_png_string(){
		$base64 = "iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAP0lEQVQImQE0AMv/ASsvCUoqH2lpSPcs/wT38wD98vzo2/UGDQIDDg0HDAUBA/8B/fwUBAYDEA8aKun/Egf4GADOEqIfhaDkAAAAAElFTkSuQmCC";

		$expected = 'style="background-size: 100% 100%; background-image: url(\'data:image/png;base64,';
		$src = 'uploads/banners/page/cater.jpg';

		$result = (new Background($src))->render();

		$this->assertEquals(
			$expected,
			substr($result, 0, 80)
		);

		$this->assertEquals(
			271,
			strlen($result)
		);



	}

	/** @test */
	function empty_source_returns_image_not_found(){
		$base64 = "MissingBGImage";

		$expected = 'style="background-size: 100% 100%; background-image: url(\'data:image/png;base64,'.$base64.'\')"';
		$src = 'uploads/banners/';

		$result = (new Background($src))->render();

		$this->assertEquals(
			$expected,
			$result
		);
	}
}