<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Rendering a tag should only build urls. All image work happens later, in the image route.
 */
class RenderSideEffectsTest extends GlideTestCase
{

	function test_rendering_a_page_of_images_does_no_http_queue_or_storage_work()
	{
		Http::fake();
		Queue::fake();

		$this->blade(
			'<picture><x-pic src="/img/one.jpg" :properties="[300, 200]"/></picture>'
			. '<x-img src="/img/two.jpg" :properties="[[300, 180], [1024, 300], 2048]"/>'
			. '<x-img src="http://localhost/img/three.jpg" width="320"/>'
		);

		Http::assertNothingSent();
		Queue::assertNothingPushed();
		$this->assertEmpty(Storage::disk('bic-cache')->allFiles());
	}

	function test_rendering_works_when_the_source_image_does_not_exist()
	{
		$html = (string) $this->blade('<x-img src="/img/missing.jpg" :properties="[300, 200]"/>');

		[$srcset] = $this->srcsets($html);
		$this->assertSignedImageUrl($srcset[0][0], 'img/missing.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}
}
