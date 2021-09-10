<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\BladeImageCrop;
use DNABeast\BladeImageCrop\Source;
use Illuminate\Support\Facades\Config;
use Mockery;
use Orchestra\Testbench\TestCase;

class SourceTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();

		Config::set('bladeimagecrop', [
			'disk' => 'public',
			'images_from_public_path' => true,
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'],
			'text_labels' => env('BLADE_CROP_TEST_LABELS', false),
			'build_classes' => [
				'webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder',
				'jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder',
				// 'jpg' => 'DNABeast\BladeImageCrop\Builder\ShortPixelJPGBuilder',
			]
		]);
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => $path,
		]);
	}

	/** @test */
	function return_source_object_with_all_options(){

		$response = '/image_jpg/800x600_50_50.jpg';

		$this->swap(BladeImageCrop::class, new FakeBladeImageCrop($response));

		$expected = <<<EOT
		<source type="image/jpeg" srcset="/image_jpg/800x600_50_50.jpg 800w" sizes="string content">
		EOT;

		$options = [
			'src' => 'image.jpg',
			'format' => 'jpeg',
			'properties' => [800, 600, 50, 50],
			'sizes' => 'string content'
		];

		$source = Source::make($options);

		$this->assertEquals(
			$expected,
			$source->render()
		);

	}


	/** @test */
	function calculate_the_uris_from_the_srcset_lines(){

		$response = '/image_jpg/800x600_50_50.jpg';

		$this->swap(BladeImageCrop::class, new FakeBladeImageCrop($response));

		$expected = '/image_jpg/800x600_50_50.jpg 800w';


		$options = [
			'src' => 'image.jpg',
			'format' => 'jpeg',
			'properties' => [800, 600, 50, 50],
			'sizes' => 'string content'
		];

		$source = Source::make($options);

		$this->assertEquals(
			$expected,
			$source->srcsetLines()
		);


	}

	/** @test */
	function calculate_the_uris_pixel_ratio_from_the_srcset_lines(){

		$response = '/image_jpg/800x600_50_50.jpg';

		$this->swap(BladeImageCrop::class, new FakeBladeImageCrop($response));

		$expected = '/image_jpg/800x600_50_50.jpg 1x';


		$options = [
			'src' => 'image.jpg',
			'format' => 'jpeg',
			'properties' => [800, 600, 50, 50],
			'sizes' => 'string content',
			'pixelRatios' => true
		];

		$source = Source::make($options);

		$this->assertEquals(
			$expected,
			$source->srcsetLines()
		);


	}

}

class FakeBladeImageCrop extends BladeImageCrop{
	public $string;

	public function __construct($string)
	{
		$this->string = $string;
	}
	public function fire($url, $dimensions, $offset = [50, 50], $format = 'jpg'){
		return $this->string;
	}
}