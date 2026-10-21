<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class ImageBuilderTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();
	}

	function test_creates_and_saves_a_jpeg_image()
	{
		// Set up fake storage and configuration
		Storage::fake('public');
		Config::set('bladeimagecrop.disk', 'public');
		Config::set('bladeimagecrop.build_classes', ['jpg' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder']);

		// Create and save a fake jpeg image
		$format = 'jpg';
		$uri = 'uploads/temp/image.jpg';
		$image = imagecreatetruecolor(2200, 900);
		$magenta = imagecolorallocate($image, 255, 0, 255);
		imagefill($image, 10, 10, $magenta);

		// Capture image as a blob
		ob_start();
		imagejpeg($image);
		$blob = ob_get_clean();

		// Save blob to fake storage
		Storage::disk('public')->put($uri, $blob);
		Http::fake([
			'*' => Http::response($blob, 200),
		]);

		$originUrl = Storage::disk('public')->url($uri);

		// Test if URL is generated
		$this->assertNotEmpty($originUrl, 'Failed to generate URL for the saved image.');

		// Utilize ImageBuilder functionality
		$destinationPath = 'uploads/temp/image_processed.jpg';
		(new ImageBuilder($originUrl, $format))->save($destinationPath);

		// Assert the MIME type and existence in storage
		$this->assertEquals(
			'image/jpeg',
			Storage::disk('public')->mimeType($uri)
		);

		$this->assertTrue(Storage::disk('public')->exists($destinationPath), 'Image was not processed and saved correctly.');

		// Cleanup fake storage
		imagedestroy($image);
		Storage::disk('public')->delete($uri);
		Storage::disk('public')->delete($destinationPath);
	}

	function test_creates_and_saves_a_webp_image(){
		// Set up fake storage and configuration
		Storage::fake('public');
		Config::set('bladeimagecrop.disk', 'public');
		Config::set('bladeimagecrop.build_classes', ['webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder']);

		// Create and save a fake jpeg image
		$format = 'webp';
		$uri = 'uploads/temp/image.webp';
		$image = imagecreatetruecolor(2200, 900);
		$magenta = imagecolorallocate($image, 255, 0, 255);
		imagefill($image, 10, 10, $magenta);

		// Capture image as a blob
		ob_start();
		imagewebp($image);
		$blob = ob_get_clean();

		// Save blob to fake storage
		Storage::disk('public')->put($uri, $blob);
		Http::fake([
			'*' => Http::response($blob, 200),
		]);

		$originUrl = Storage::disk('public')->url($uri);

		// Test if URL is generated
		$this->assertNotEmpty($originUrl, 'Failed to generate URL for the saved image.');

		// Utilize ImageBuilder functionality
		$destinationPath = 'uploads/temp/image_processed.webp';
		(new ImageBuilder($originUrl, $format))->save($destinationPath);

		// Assert the MIME type and existence in storage
		$this->assertEquals(
			'image/webp',
			Storage::disk('public')->mimeType($uri)
		);



		$this->assertTrue(Storage::disk('public')->exists($destinationPath), 'Image was not processed and saved correctly.');

		// Cleanup fake storage
		imagedestroy($image);
		Storage::disk('public')->delete($uri);
		Storage::disk('public')->delete($destinationPath);

	}



}
