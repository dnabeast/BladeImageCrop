<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use GdImage;
use Imagick;
use PHPUnit\Framework\Attributes\DataProvider;

class DriverTest extends GlideTestCase
{

	public static function drivers(): array
	{
		return [
			'gd' => ['gd'],
			'imagick' => ['imagick'],
		];
	}

	private function useDriver(string $driver): void
	{
		if (! extension_loaded($driver)) {
			$this->markTestSkipped("The $driver extension isn't installed");
		}

		config()->set('bladeimagecrop.driver', $driver);
		$this->app->forgetInstance('bladeimagecrop.server.disk');
		$this->app->forgetInstance('bladeimagecrop.server.public');
	}

	function test_the_driver_defaults_to_gd()
	{
		$this->assertInstanceOf(GdImage::class, $this->nativeImage('disk'));
	}

	#[DataProvider('drivers')]
	function test_every_source_uses_the_configured_driver($driver)
	{
		$this->useDriver($driver);
		$nativeClass = $driver === 'gd' ? GdImage::class : Imagick::class;

		$this->assertInstanceOf($nativeClass, $this->nativeImage('disk'));
		$this->assertInstanceOf($nativeClass, $this->nativeImage('public'));
	}

	#[DataProvider('drivers')]
	function test_the_configured_driver_crops_images($driver)
	{
		$this->useDriver($driver);
		$this->putSplitImage('img/photo.jpg', 800, 600);

		$response = $this->get($this->signedUrl('img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'webp']));

		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([300, 200], [$size[0], $size[1]]);
	}

	private function nativeImage(string $source): object
	{
		return $this->app->make('bladeimagecrop.server.' . $source)->getApi()->getImageManager()->createImage(1, 1)->core()->native();
	}
}
