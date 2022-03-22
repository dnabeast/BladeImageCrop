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
		Config::set('app.env', 'http://test.com');

		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => $path,
			'url' => 'http://test.com/uploads'
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

	/** @test */
	function trim_storage_folder_from_uri(){
		Config::set('bladeimagecrop.images_from_public_path', true);

		$expected = 'temp/image.jpg';

		$result = (new UriHelper)->trim('/uploads/temp/image.jpg');

		$this->assertEquals(
			$expected,
			$result
		);
	}

}