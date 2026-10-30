<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\BladeImageCrop;
use DNABeast\BladeImageCrop\Helper;
use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class ImageCropTest extends TestCase
{

	public function setUp() :void
	{
		parent::setUp();
		Config::set('bladeimagecrop',[
			'disk' => 'public',
			'build_classes' => [
				'jpg' => 'DNABeast\BladeImageCrop\Builder\IM_JPGBuilder',
				'webp' => 'DNABeast\BladeImageCrop\Builder\IM_WebPBuilder'
			]
		]);
		Config::set('filesystems.disks.public', [
			'driver' => 'local',
			'root' => storage_path('app/public'),
		]);
		Storage::fake('public');

		$this->app->bind('path.public', function() {
			return str_replace("vendor/orchestra/testbench-core/laravel", "tests/Unit/storage", base_path());
		});
	}

	function test_if_no_image_found_return_imagenotfound(){

		$url = 'uploads/banners/page/doesntexist.jpg';
		$dimensions = [400, 300];

		$newImageUrl = (new BladeImageCrop(new Helper()))->fire($url, $dimensions);

		$this->assertEquals(
			'IMAGE_NOT_FOUND__uploads/banners/page/doesntexist.jpg',
			$newImageUrl
		);

	}


	function test_if_correct_file_exists_return_file_url(){

		$path = '/uploads/banners/page/cater.jpg';
		$dimensions = ['width' => 400, 'height' => 200];
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$path);
		$url = 'http://localhost'.$path;
		Cache::set('bic_props_'.$url, [
			800,
			600,
		]);
		Http::fake([
			$url => $image,
		]);

		$newImageUrl = (new BladeImageCrop(new Helper()))->fire($url, $dimensions);

		$this->assertEquals(
			$url,
			$newImageUrl
		);

		$this->assertTrue(
			Storage::disk('public')->has('bic/uploads/banners/page/cater_jpg/bic_400x200_50_50.jpg')
		);

	}

	function test_if_correct_file_exists_with_webp_format_return_file_url(){

		$path = '/uploads/banners/page/cater.jpg';
		$dimensions = ['width' => 400, 'height' => 300];
		$offset = ['x'=>50, 'y'=>50];
		$image = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$path);
		$url = 'http://localhost'.$path;
		Cache::set('bic_props_'.$url, [
			800,
			600,
		]);
		Http::fake([
			$url => $image,
		]);


		(new BladeImageCrop(new Helper()))->fire($url, $dimensions, $offset, 'webp');

		$this->assertTrue(
			Storage::disk('public')->has('bic/uploads/banners/page/cater_jpg/bic_400x300_50_50.webp')
		);

	}



	function test_if_correct_file_doesnt_exists_return_file_url_and_make_new_directory_and_file(){

		$path = 'banners/page/grid.png';
		$options = ['targetWidth'=>500, 'targetHeight'=>250, 'cropWidth'=>50, 'cropHeight'=>50, 'x'=>50, 'y'=>50];
		$image = file_get_contents(__DIR__.'/uploads/'.$path);

		$url = 'http://localhost'.$path;

		Http::fake([
			$url => $image,
		]);

//		$newImageUrl = (new BladeImageCrop(new Helper()))->fire($path, $dimensions);

        $imageUrl = Storage::disk( config('bladeimagecrop.disk') )->url($url);
        (new ImageBuilder($imageUrl, 'jpg'))->resize($options)->save('test.jpg');

		$this->assertTrue(
            Storage::disk( config('bladeimagecrop.disk') )->has('test.jpg')
		);

		$this->assertTrue(
			Storage::disk('public')->has('test.jpg')
		);

		$newImage = getimagesizefromstring(Storage::disk('public')->get('test.jpg'));

		$this->assertEquals(
			500, // width
			$newImage[0]
		);

	}

	function test_update_url_returns_proper_url(){
		$result = (new BladeImageCrop(new Helper()))->updatePath('uploads/cater.jpg', ['width'=>800,'height'=>600], ['x'=>50, 'y'=>50], 'jpg');
		$this->assertEquals(
			'bic/uploads/cater_jpg/bic_800x600_50_50.jpg',
			$result
		);

		$result = (new BladeImageCrop(new Helper()))->updatePath('cater.jpg', ['width'=>800,'height'=>600], ['x'=>50, 'y'=>50], 'jpg');
		$this->assertEquals(
			'bic/cater_jpg/bic_800x600_50_50.jpg',
			$result
		);
	}

	function test_if_url_has_directory_but_is_not_a_file_it_should_return_image_not_found(){
		Config::set('bladeimagecrop.disk', 'public');

		$path = '/uploads/test.txt';
		$dimensions = ['width'=>400,'height'=>300];
		$not_image = 'Not an image';

		$url = 'http://localhost'.$path;

		Http::fake([
			$url => $not_image,
		]);

		$newImageUrl = (new BladeImageCrop(new Helper()))->fire($url, $dimensions);

		$this->assertEquals(
			'IMAGE_NOT_FOUND__http://localhost/uploads/test.txt',
			$newImageUrl
		);
	}

	function test_if_file_has_no_extensions_should_return_image_not_found(){
		$path = 'banners/page/caterjpg';
		$dimensions = ['width'=>400,'height'=>300];
		$image = file_get_contents(__DIR__.'/uploads/banners/page/cater.jpg');

		$url = 'http://localhost'.$path;

		Http::fake([
			$url => $image,
		]);

		$newImageUrl = (new BladeImageCrop(new Helper()))->fire($url, $dimensions);

		$this->assertEquals(
			'IMAGE_NOT_FOUND__http://localhostbanners/page/caterjpg',
			$newImageUrl
		);
	}

	function test_get_newly_calculated_size_options_for_the_builder(){
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


		$result = (new BladeImageCrop(new Helper()))->options($data, $dimensions, $offset);

		$this->assertEquals(
			$expected,
			$result
		);
	}

	function test_dont_resize_image_if_target_is_bigger_than_original_image(){

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

			$result = (new BladeImageCrop(new Helper()))->options($data, $dimensions, $offset);

			$this->assertEquals(
				$expected,
				$result
			);
		}

}
