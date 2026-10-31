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
			'background_builder' => 'DNABeast\BladeImageCrop\BGBuilder',
			'background_colors' => ['DarkKhaki'],
		]);
	}

	function test_src_of_image_returns_gradient_style(){

		$src = 'uploads/banners/page/cater.jpg';

		$result = (new Background($src))->render();

		$this->assertStringStartsWith('style="background-size: 100% 100%; background-image:', $result);
		$this->assertStringContainsString('gradient(', $result);
		$this->assertStringNotContainsString('url(', $result);

	}


}