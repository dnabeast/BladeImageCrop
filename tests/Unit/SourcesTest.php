<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\View\Components\Sources;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
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
			'trim_directory' => 'storage',
			'offset_x' => 50, // percentage
			'offset_y' => 50,
			'pixel_device_ratios' => ['1x', '2x'], // add multipliers here for ultra high def screens
			'backgrounds' => true,
			'text_labels' => env('BLADE_CROP_TEST_LABELS', false), // These labels get written to the created images if they're not yet created.
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
	function if_blade_command_sources_requested_return_a_html_string(){


		$expected = <<<EOT
		<source media="test" type="image/webp" srcset="/uploads/banners/page/cater_jpg/800x600_50_50.webp 1x,/uploads/banners/page/cater_jpg/1600x1200_50_50.webp 2x" sizes="sizeTest">
		<source media="test" type="image/jpeg" srcset="/uploads/banners/page/cater_jpg/800x600_50_50.jpg 1x,/uploads/banners/page/cater_jpg/1600x1200_50_50.jpg 2x" sizes="sizeTest">
		EOT;

		$result = $this->blade('<x-sources src="uploads/banners/page/cater.jpg" :properties="[800, 600]" media="test" sizes="sizeTest"/>');

		$result->assertSee($expected, false );

	}

	/** @test */
	function if_blade_command_sources_requested_with_many_properties_return_a_html_string(){

		$expected = <<<EOT
		<source media="test" type="image/webp" srcset="/uploads/banners/page/cater_jpg/800x600_50_50.webp 800w,/uploads/banners/page/cater_jpg/1024x768_50_50.webp 1024w" sizes="sizeTest">
		<source media="test" type="image/jpeg" srcset="/uploads/banners/page/cater_jpg/800x600_50_50.jpg 800w,/uploads/banners/page/cater_jpg/1024x768_50_50.jpg 1024w" sizes="sizeTest">
		EOT;

		$result = $this->blade('<x-sources src="uploads/banners/page/cater.jpg" :properties="[[800, 600], 1024]" media="test" sizes="sizeTest"/>');

		$result->assertSee($expected, false );

	}

	// assert contains only instances of

	/** @test */
	function if_properties_is_not_dynamic_throw_exception(){

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Properties must use : as a prefix.');

		$result = $this->blade('<x-sources src="uploads/banners/page/cater.jpg" properties="[800, 600]"/>');


	}

}