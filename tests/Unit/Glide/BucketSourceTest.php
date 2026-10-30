<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use Illuminate\Support\Facades\Storage;

class BucketSourceTest extends GlideTestCase
{
	public function setUp(): void
	{
		parent::setUp();

		// Stand-in for a bucket disk: its urls are on a different host to the app
		Storage::fake('bic-source', ['url' => 'https://my-bucket.r2.example.com']);
	}

	function test_a_bucket_url_is_signed_against_its_path_on_the_source_disk()
	{
		$html = (string) $this->blade('<x-img src="https://my-bucket.r2.example.com/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertSignedImageUrl($this->srcsets($html)[0][0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_the_route_serves_an_image_given_as_a_bucket_url()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600);

		$html = (string) $this->blade('<x-img src="' . Storage::disk('bic-source')->url('img/photo.jpg') . '" :properties="[300, 200]"/>');

		$response = $this->get($this->srcsets($html)[0][0][0]);
		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([300, 200], [$size[0], $size[1]]);
	}

	function test_urls_on_other_hosts_are_still_left_alone()
	{
		$html = (string) $this->blade('<x-img src="https://elsewhere.example.com/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertStringNotContainsString('/bic/', $html);
		$this->assertStringContainsString('src="https://elsewhere.example.com/img/photo.jpg"', $html);
	}
}