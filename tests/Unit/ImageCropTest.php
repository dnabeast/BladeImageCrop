<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\BladeImageCrop;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class ImageCropTest extends TestCase
{

	/** @test */
	function if_no_image_found_return_null(){

		$url = 'banners/page/cater.jpg';
		$dimensions = [400, 300];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);

		// $image = Storage::disk('tests')->get('Unit/uploads/'.$url);

		Storage::fake('uploads');

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'imageNotFound',
			$newImageUrl
		);

	}


	/** @test */
	function if_correct_file_exists_return_file_url(){


		$url = 'banners/page/cater.jpg';
		$dimensions = [400, 300];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);

		Storage::fake('uploads');
		Storage::disk('uploads')->put($url, $image);
		Storage::disk('uploads')->put('banners/page/cater_jpg/400x300_50_50.jpg', $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'/uploads/banners/page/cater_jpg/400x300_50_50.jpg',
			$newImageUrl
		);

	}

	/** @test */
	function if_correct_file_doens_t_exists_return_file_url_and_make_new_directory_and_file(){
		Config::set('shortpixel.active', false);

		$url = 'banners/page/grid.png';
		$dimensions = [500, 250];
		$image = file_get_contents(__DIR__.'/uploads/'.$url);
		Storage::fake('uploads');
		Storage::disk('uploads')->put($url, $image);

		$newImageUrl = (new BladeImageCrop)->fire($url, $dimensions);

		$this->assertEquals(
			'/uploads/banners/page/grid_png/500x250_50_50.jpg',
			$newImageUrl
		);
		$newImageUrl = str_replace('uploads/', '', $newImageUrl);

		$this->assertTrue(
			Storage::disk('uploads')->has($newImageUrl)
		);

		$newImage = getimagesizefromstring(Storage::disk('uploads')->get($newImageUrl));

		$this->assertEquals(
			500, // width
			$newImage[0]
		);

	}


}
