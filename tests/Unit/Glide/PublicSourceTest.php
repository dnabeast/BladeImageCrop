<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Images that live in the app's public folder rather than on the source disk, e.g. Vite builds in public/build/assets.
 */
class PublicSourceTest extends GlideTestCase
{
	function test_a_same_host_src_found_in_the_public_folder_is_signed_against_the_public_source()
	{
		$this->putSplitImage('build/assets/hero-a1b2c3.jpg', 800, 600, 'public');

		$html = (string) $this->blade('<x-img src="http://localhost/build/assets/hero-a1b2c3.jpg" :properties="[300, 200]"/>');

		$this->assertSignedImageUrl($this->srcsets($html)[0][0][0], 'build/assets/hero-a1b2c3.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg'], 'public');
	}

	function test_a_src_on_the_requested_host_is_found_when_the_app_answers_on_a_second_domain()
	{
		$this->putSplitImage('build/assets/hero-a1b2c3.jpg', 800, 600, 'public');
		$this->app->instance('request', Request::create('http://second-domain.test/'));

		$html = (string) $this->blade('<x-img src="http://second-domain.test/build/assets/hero-a1b2c3.jpg" :properties="[300, 200]"/>');

		$this->assertStringStartsWith('http://second-domain.test/bic/public/build/assets/hero-a1b2c3.jpg?', $this->srcsets($html)[0][0][0]);
	}

	function test_a_src_on_the_apps_main_host_is_found_when_app_url_has_no_subdomain()
	{
		config()->set('app.url', 'http://testsite.com');
		$this->putSplitImage('build/assets/hero-a1b2c3.jpg', 800, 600, 'public');
		$this->app->instance('request', Request::create('http://testsite.com/'));

		$html = (string) $this->blade('<x-img src="http://testsite.com/build/assets/hero-a1b2c3.jpg" :properties="[300, 200]"/>');

		$this->assertStringStartsWith('http://testsite.com/bic/public/build/assets/hero-a1b2c3.jpg?', $this->srcsets($html)[0][0][0]);
	}

	function test_a_src_on_a_subdomain_of_the_app_is_found()
	{
		config()->set('app.url', 'http://testsite.com');
		$this->putSplitImage('build/assets/hero-a1b2c3.jpg', 800, 600, 'public');
		$this->app->instance('request', Request::create('http://first.testsite.com/'));

		$html = (string) $this->blade('<x-img src="http://first.testsite.com/build/assets/hero-a1b2c3.jpg" :properties="[300, 200]"/>');

		$this->assertStringStartsWith('http://first.testsite.com/bic/public/build/assets/hero-a1b2c3.jpg?', $this->srcsets($html)[0][0][0]);
	}

	function test_a_src_on_another_subdomain_of_the_app_is_found()
	{
		config()->set('app.url', 'http://testsite.com');
		$this->putSplitImage('build/assets/hero-a1b2c3.jpg', 800, 600, 'public');
		$this->app->instance('request', Request::create('http://first.testsite.com/'));

		$html = (string) $this->blade('<x-img src="http://second.testsite.com/build/assets/hero-a1b2c3.jpg" :properties="[300, 200]"/>');

		$this->assertStringStartsWith('http://first.testsite.com/bic/public/build/assets/hero-a1b2c3.jpg?', $this->srcsets($html)[0][0][0]);
	}

	function test_a_host_that_only_ends_with_the_app_host_is_left_alone()
	{
		config()->set('app.url', 'http://testsite.com');
		$this->app->instance('request', Request::create('http://testsite.com/'));

		$html = (string) $this->blade('<x-img src="http://eviltestsite.com/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertStringNotContainsString('/bic/', $html);
		$this->assertStringContainsString('src="http://eviltestsite.com/img/photo.jpg"', $html);
	}

	function test_the_route_serves_an_image_from_the_public_folder()
	{
		$this->putSplitImage('build/assets/hero-a1b2c3.jpg', 800, 600, 'public');

		$html = (string) $this->blade('<x-img src="/build/assets/hero-a1b2c3.jpg" :properties="[300, 200]"/>');

		$response = $this->get($this->srcsets($html)[0][0][0]);
		$response->assertOk();
		$size = getimagesizefromstring($this->responseBody($response));
		$this->assertEquals([300, 200], [$size[0], $size[1]]);
	}

	function test_a_src_not_in_the_public_folder_still_uses_the_source_disk()
	{
		$this->putSplitImage('img/photo.jpg');

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertSignedImageUrl($this->srcsets($html)[0][0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_the_source_disk_is_used_when_the_public_source_is_off()
	{
		config()->set('bladeimagecrop.public_source', false);
		$this->putSplitImage('build/assets/hero.jpg', 800, 600, 'public');

		$html = (string) $this->blade('<x-img src="/build/assets/hero.jpg" :properties="[300, 200]"/>');

		$this->assertSignedImageUrl($this->srcsets($html)[0][0][0], 'build/assets/hero.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_the_route_refuses_the_public_source_when_it_is_off()
	{
		config()->set('bladeimagecrop.public_source', false);
		$this->putSplitImage('build/assets/hero.jpg', 800, 600, 'public');

		$this->get($this->signedUrl('build/assets/hero.jpg', ['w' => 300, 'fm' => 'jpg'], 'public'))->assertNotFound();
	}

	function test_width_only_images_from_the_public_folder_get_a_height()
	{
		$this->putSplitImage('build/assets/hero.jpg', 800, 600, 'public');

		$html = (string) $this->blade('<x-img src="/build/assets/hero.jpg" properties="400"/>');

		$this->assertEquals('300', $this->attribute($html, 'img', 'height'));
	}

	function test_the_same_path_on_each_source_is_cropped_and_cached_separately()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600);
		$this->putSplitImage('img/photo.jpg', 400, 400, 'public');

		$disk = $this->get($this->signedUrl('img/photo.jpg', ['w' => 100, 'fm' => 'jpg']));
		$public = $this->get($this->signedUrl('img/photo.jpg', ['w' => 100, 'fm' => 'jpg'], 'public'));

		$this->assertEquals(75, getimagesizefromstring($this->responseBody($disk))[1]);
		$this->assertEquals(100, getimagesizefromstring($this->responseBody($public))[1]);
		$this->assertNotEmpty(Storage::disk('bic-cache')->allFiles('bic/disk'));
		$this->assertNotEmpty(Storage::disk('bic-cache')->allFiles('bic/public'));
	}

	function test_the_source_in_a_url_cannot_be_swapped()
	{
		$this->putSplitImage('img/photo.jpg', 800, 600, 'public');

		$url = $this->signedUrl('img/photo.jpg', ['w' => 300, 'fm' => 'jpg']);

		$this->get(str_replace('/bic/disk/', '/bic/public/', $url))->assertForbidden();
	}

	function test_paths_outside_the_public_folder_are_rejected()
	{
		$status = $this->get($this->signedUrl('../.env', ['w' => 300, 'fm' => 'jpg'], 'public'))->status();

		$this->assertContains($status, [400, 403, 404]);
	}
}
