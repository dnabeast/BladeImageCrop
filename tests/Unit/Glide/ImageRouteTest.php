<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use Illuminate\Support\Facades\Storage;

class ImageRouteTest extends GlideTestCase
{

	function test_a_signed_request_returns_the_cropped_image_in_the_requested_format()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600);

		$response = $this->get($this->signedUrl('img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'webp']));

		$response->assertOk();
		$this->assertEquals('image/webp', $response->headers->get('Content-Type'));

		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([300, 200], [$size[0], $size[1]]);
	}

	function test_urls_generated_by_the_component_are_served_by_the_route()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600);

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');
		$url = $this->srcsets($html)[0][1][0];

		$response = $this->get($url);

		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([600, 400], [$size[0], $size[1]]);
	}

	function test_width_only_keeps_the_original_aspect_ratio()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600);

		$response = $this->get($this->signedUrl('img/photo.jpg', ['w' => 400, 'fm' => 'jpg']));

		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([400, 300], [$size[0], $size[1]]);
	}

	function test_focal_point_moves_the_crop()
	{
		// 200x100 source, left half red, right half blue. A 50x100 crop can sit entirely in either half.
		$this->putSplitImage('img/split.png', 200, 100);

		$left = $this->get($this->signedUrl('img/split.png', ['w' => 50, 'h' => 100, 'fit' => 'crop-0-50', 'fm' => 'png']));
		$right = $this->get($this->signedUrl('img/split.png', ['w' => 50, 'h' => 100, 'fit' => 'crop-100-50', 'fm' => 'png']));

		$this->assertEquals([255, 0, 0], $this->centreColour($this->responseBody($left)));
		$this->assertEquals([0, 0, 255], $this->centreColour($this->responseBody($right)));
	}

	function test_images_in_nested_folders_are_found()
	{
		$this->putSplitImage('uploads/banners/page/cater.jpg', 400, 300);

		$this->get($this->signedUrl('uploads/banners/page/cater.jpg', ['w' => 100, 'h' => 100, 'fit' => 'crop-50-50', 'fm' => 'jpg']))
			->assertOk();
	}

	function test_the_generated_image_is_stored_on_the_cache_disk()
	{
		$this->putSplitImage('img/photo.jpg');

		$this->get($this->signedUrl('img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']))->assertOk();

		$this->assertNotEmpty(Storage::disk('bic-cache')->allFiles());
	}

	function test_a_second_request_is_served_from_the_cache_without_reading_the_source()
	{
		$this->putSplitImage('img/photo.jpg');
		$url = $this->signedUrl('img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);

		$this->get($url)->assertOk();
		Storage::disk('bic-source')->delete('img/photo.jpg');

		$response = $this->get($url);

		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([300, 200], [$size[0], $size[1]]);
	}

	function test_responses_can_be_cached_by_browsers_and_cdns()
	{
		$this->putSplitImage('img/photo.jpg');

		$response = $this->get($this->signedUrl('img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']));

		$cacheControl = $response->headers->get('Cache-Control');
		$this->assertStringContainsString('public', $cacheControl);
		$this->assertMatchesRegularExpression('/max-age=(\d{7,})/', $cacheControl, 'Expected a max-age of at least a few months');
	}

	function test_an_unsigned_request_is_rejected()
	{
		$this->putSplitImage('img/photo.jpg');

		$this->get('/bic/img/photo.jpg?w=300&h=200&fit=crop-50-50&fm=jpg')->assertForbidden();
	}

	function test_a_tampered_request_is_rejected()
	{
		$this->putSplitImage('img/photo.jpg');

		$url = $this->signedUrl('img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);

		$this->get(str_replace('w=300', 'w=1999', $url))->assertForbidden();
	}

	function test_a_missing_source_returns_not_found()
	{
		$this->get($this->signedUrl('img/missing.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']))
			->assertNotFound();
	}

	function test_a_file_that_is_not_an_image_does_not_return_a_server_error()
	{
		Storage::disk('bic-source')->put('uploads/test.jpg', 'Not an image');

		$status = $this->get($this->signedUrl('uploads/test.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']))->status();

		$this->assertContains($status, [404, 415, 422]);
	}

	function test_paths_outside_the_source_disk_are_rejected()
	{
		$status = $this->get($this->signedUrl('../../.env', ['w' => 300, 'fm' => 'jpg']))->status();

		$this->assertContains($status, [400, 403, 404]);
	}

	function test_output_is_limited_by_max_image_size()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600);

		$response = $this->get($this->signedUrl('img/photo.jpg', ['w' => 4000, 'h' => 3000, 'fit' => 'crop-50-50', 'fm' => 'jpg']));

		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertLessThanOrEqual(2000 * 2000, $size[0] * $size[1]);
	}
}
