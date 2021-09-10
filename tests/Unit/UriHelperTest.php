<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\UriHelper;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class UriHelperTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();
		Config::set('bladeimagecrop.disk', 'public');
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => $path,
		]);
		$this->uri = "uploads/temp/image.jpg";
	}

	/** @test */
	function get_directory_from_uri(){



		$expected = __DIR__.'/uploads/temp';

		$result = (new UriHelper)->directory($this->uri);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function get_filename_from_uri(){

		$expected = 'image';

		$result = (new UriHelper)->filename($this->uri);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function get_path_from_uri(){

		$expected = __DIR__.'/uploads/temp/image.jpg';

		$result = (new UriHelper)->path($this->uri);

		$this->assertEquals(
			$expected,
			$result
		);
	}

}