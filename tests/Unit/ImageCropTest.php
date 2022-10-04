<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\BladeImageCrop;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class ImageCropTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();
		Config::set('bladeimagecrop',[
			'short_pixel_active' => env('BLADE_CROP_SHORTPIXEL', false),
			'disk' => 'public',
			'build_classes' => [
				'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
				'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder'
			]
		]);
		$path = __DIR__;
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => storage_path('app/public'),
		]);

		$this->app->bind('path.public', function() {
			return str_replace("vendor/orchestra/testbench-core/laravel", "tests/Unit/storage", base_path());
		});
	}

	/** @test */
	function if_no_image_found_return_imagenotfound(){

		$url = 'uploads/banners/page/doesntexist.jpg';
		$dimensions = [400, 300];

		Storage::fake('public');

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'IMAGENOTFOUND',
			$newImageUrl
		);

	}


	/** @test */
	function if_correct_file_exists_return_file_url(){

		$url = 'uploads/banners/page/cater.jpg';
		$dimensions = ['width' => 400, 'height' => 300];
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$url);

		Storage::fake('public');
		Storage::disk('public')->put($url, $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'/uploads/banners/page/cater_jpg/400x300_50_50.jpg',
			$newImageUrl
		);

		$this->assertTrue(
			Storage::disk('public')->has('/uploads/banners/page/cater_jpg/400x300_50_50.jpg')
		);

	}

	/** @test */
	function if_correct_file_exists_with_webp_format_return_file_url(){

		$url = 'banners/page/cater.jpg';
		$dimensions = ['width' => 400, 'height' => 300];
		$offset = ['x'=>50, 'y'=>50];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);

		Storage::fake('public');
		Storage::disk('public')->put($url, $image);
		// Storage::disk('public')->put('banners/page/cater_jpg/400x300_50_50.jpg', $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions, $offset, 'webp');

		$this->assertEquals(
			'/banners/page/cater_jpg/400x300_50_50.webp',
			$newImageUrl
		);

		$this->assertTrue(
			Storage::disk('public')->has('/banners/page/cater_jpg/400x300_50_50.webp')
		);

	}

	/** @test */
	function if_correct_file_exists_on_correct_disk_return_file_url(){
		Config::set('bladeimagecrop.disk', 'uploads');

		$url = 'banners/page/cater.jpg';
		$dimensions = [400, 300];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);

		Storage::fake('uploads');
		Storage::disk('uploads')->put($url, $image);
		Storage::disk('uploads')->put('banners/page/cater_jpg/400x300_50_50.jpg', $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'/banners/page/cater_jpg/400x300_50_50.jpg',
			$newImageUrl
		);

	}

	/** @test */
	function if_correct_file_in_correct_directory_exists_return_file_url(){

		$url = 'banners/page/cater.jpg';
		$dimensions = [400, 300];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);

		Storage::fake('public');
		Storage::disk('public')->put($url, $image);
		Storage::disk('public')->put('banners/page/cater_jpg/400x300_50_50.jpg', $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'/banners/page/cater_jpg/400x300_50_50.jpg',
			$newImageUrl
		);

	}

	/** @test */
	function if_correct_file_doens_t_exists_return_file_url_and_make_new_directory_and_file(){

		$url = 'banners/page/grid.png';
		$dimensions = ['width'=>500, 'height'=>250];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);

		Storage::fake('public');
		Storage::disk('public')->put($url, $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'/banners/page/grid_png/500x250_50_50.jpg',
			$newImageUrl
		);
		$newImageUrl = str_replace('uploads/', '', $newImageUrl);

		$this->assertTrue(
			Storage::disk('public')->has($newImageUrl)
		);

		$newImage = getimagesizefromstring(Storage::disk('public')->get($newImageUrl));

		$this->assertEquals(
			500, // width
			$newImage[0]
		);

	}

	/** @test */
	function update_url_returns_proper_url(){
		$result = (new BladeImageCrop)->updateUrl('uploads/cater.jpg', ['width'=>800,'height'=>600], ['x'=>50, 'y'=>50], 'jpg');
		$this->assertEquals(
			'/uploads/cater_jpg/800x600_50_50.jpg',
			$result
		);

		$result = (new BladeImageCrop)->updateUrl('cater.jpg', ['width'=>800,'height'=>600], ['x'=>50, 'y'=>50], 'jpg');
		$this->assertEquals(
			'/cater_jpg/800x600_50_50.jpg',
			$result
		);
	}

	/** @test */
	function if_url_has_directory_but_is_not_a_file_it_should_return_image_not_found(){
		Config::set('bladeimagecrop.disk', 'uploads');
		Config::set('bladeimagecrop.images_from_public_path', true);

		$url = 'uploads/';
		$dimensions = ['width'=>400,'height'=>300];

		Storage::fake('uploads');
		Storage::disk('uploads')->put($url.'test.txt', 'test');

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'IMAGENOTFOUND',
			$newImageUrl
		);
	}

	/** @test */
	function if_file_has_no_extensions_should_return_image_not_found(){
		$url = 'banners/page/caterjpg';
		$dimensions = ['width'=>400,'height'=>300];
		$image = file_get_contents(__DIR__.'/uploads/banners/page/cater.jpg');

		Storage::fake('public');
		Storage::disk('public')->put($url, $image);
		Storage::disk('public')->put('banners/page/cater_jpg/400x300_50_50.jpg', $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'IMAGENOTFOUND',
			$newImageUrl
		);
	}

	/** @test */
	function if_file_is_not_an_image_return_false(){

		$url = 'uploads/banners/page/isImage.jpg';
		$url2 = 'uploads/banners/page/isText.txt';

		$image = file_get_contents(__DIR__.'/uploads/banners/page/cater.jpg');

		Storage::fake('public');
		Storage::disk('public')->put($url, $image);

		$this->assertFalse(
			(new BladeImageCrop)->fileNotImage($url),
		);

		Storage::disk('public')->put($url2, 'This is some Text');

		$this->assertTrue(
			(new BladeImageCrop)->fileNotImage($url2),
		);
	}

	/** @test */
	function get_newly_calculated_size_options_for_the_builder(){
		$data = [
			1000,
			800,
		];

		$dimensions = [
			"width" => 400,
			"height" => 300
		];

		$offset = [
			'x' => 50,
			'y' => 50
 		];

		$expected = [
			'x' => 0,
			'y' => 25,
			'cropWidth' => 1000,
			'cropHeight' => 750,
			'targetWidth' => 400,
			'targetHeight' => 300
		];


		$result = (new BladeImageCrop)->options($data, $dimensions, $offset);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/** @test */
	function dont_resize_image_if_target_is_bigger_than_original_image(){

			$data = [
				500,
				250,
			];

			$dimensions = [
				"width" => 1000,
				"height" => 400
			];

			$offset = [
				'x' => 50,
				'y' => 50
	 		];

			$expected = [
				'x' => 0,
				'y' => 25,
				'cropWidth' => 500,
				'cropHeight' => 200,
				'targetWidth' => 500,
				'targetHeight' => 200
			];

			$result = (new BladeImageCrop)->options($data, $dimensions, $offset);

			$this->assertEquals(
				$expected,
				$result
			);
		}

}
