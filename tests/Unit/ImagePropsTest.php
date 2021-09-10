<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\ImageProps;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;



class ImagePropsTest extends TestCase
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
				'jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
				'webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder',
			]
		]);

	}

	/** @test */
	function take_width_and_aspect_and_return_props_array(){

		$expected = [[300,200, 50, 50],[600,400, 50, 50]];

		$result = (new ImageProps)->calc('300px', 2/3);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function take_broken_width_and_aspect_and_return_props_array(){

		$expected = [[300,100, 50, 50],[600,200, 50, 50]];

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Properties must use : as a prefix.');

		$result = (new ImageProps)->calc('[300,100]', 2/3);

	}

	/** @test */
	function take_width_and_aspect_with_changed_config_and_return_props_array(){
		Config::set('bladeimagecrop.pixel_device_ratios', ['1x', '2x', '4x']);

		$expected = [[300,200, 50, 50],[600,400, 50, 50],[1200,800, 50, 50]];

		$result = (new ImageProps)->calc('300px', 2/3);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function take_dimensions_and_return_props_array(){
		$expected = [[300,200, 50, 50],[600,400, 50, 50]];

		$result = (new ImageProps)->calc([300, 200]);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function take_full_props_plus_one_width_and_return_props_array(){
		$expected = [[300,200, 75, 50],[400,267, 75, 50]];

		$result = (new ImageProps)->calc([ [300, 200, 75], [400] ]);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function take_full_props_plus_one_width_string_and_return_props_array(){
		$expected = [[300,200, 75, 50],[400,267, 75, 50]];

		$result = (new ImageProps)->calc([ [300, 200, 75], 400 ]);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function take_two_full_props_plus_one_width_string_and_return_props_array(){
		$expected = [[300,180, 50, 50],[1024,300, 50, 50], [2048,600, 50, 50] ];

		$result = (new ImageProps)->calc([ [300, 180] , [1024, 300], 2048 ]);

		$this->assertEquals(
			$expected,
			$result
		);
	}


}