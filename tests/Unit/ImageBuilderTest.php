<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class ImageBuilderTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();
	}

	/** @test */
	function creates_and_saves_a_jpeg_image(){
		Config::set('bladeimagecrop.build_classes', ['jpg' => 'DNABeast\BladeImageCrop\Builder\JPGBuilder']);
		Config::set('bladeimagecrop.disk', 'storage');
		$path = __DIR__;
		Config::set('filesystems.disks.storage', [
			'driver' => 'local',
			'root' => $path,
		]);

		$format = 'jpg';
		$uri = 'uploads/temp/image.jpg';
		$image = imagecreatetruecolor(2200, 900);
		$magenta = imagecolorallocate($image, 255, 0, 255);
		imagefill($image, 10, 10, $magenta);

		(new ImageBuilder)->create($image, $uri, $format);

		imagedestroy($image);

		$this->assertEquals(
			'image/jpeg',
			Storage::disk( config('bladeimagecrop.disk') )->mimeType($uri)
		);

		unlink(Storage::disk( config('bladeimagecrop.disk') )->path($uri));

	}

	/** @test */
	function creates_and_saves_a_webp_image(){
		Config::set('bladeimagecrop.build_classes', ['webp' => 'DNABeast\BladeImageCrop\Builder\WebPBuilder']);
		Config::set('bladeimagecrop.disk', 'storage');
		$path = __DIR__;
		Config::set('filesystems.disks.storage', [
			'driver' => 'local',
			'root' => $path,
		]);

		$format = 'webp';
		$uri = 'uploads/temp/image.jpg';
		$image = imagecreatetruecolor(2200, 900);
		$magenta = imagecolorallocate($image, 255, 0, 255);
		imagefill($image, 10, 10, $magenta);

		(new ImageBuilder)->create($image, $uri, $format);

		imagedestroy($image);

		$this->assertEquals(
			'image/webp',
			Storage::disk( config('bladeimagecrop.disk') )->mimeType($uri)
		);

		unlink(Storage::disk( config('bladeimagecrop.disk') )->path($uri));

	}


}
