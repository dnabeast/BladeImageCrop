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
			'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder'
		]);
	}

	function test_src_of_image_returns_png_string(){

		$expected = 'style="background-size: 100% 100%; background-image:linear-gradient(#00C4FCbb, transparent 55%), url(\'data:image/png';
		$src = 'uploads/banners/page/cater.jpg';

		$result = (new Background($src))->render();

		$this->assertEquals(
			$expected,
			substr($result, 0, 116)
		);

	}


}