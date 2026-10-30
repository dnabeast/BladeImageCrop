<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use Illuminate\Support\Facades\Config;

class ImgComponentTest extends GlideTestCase
{

	function test_width_and_height_produce_signed_crop_urls_for_each_pixel_ratio()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertCount(2, $srcset);
		$this->assertEquals('1x', $srcset[0][1]);
		$this->assertEquals('2x', $srcset[1][1]);

		$this->assertSignedImageUrl($srcset[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
		$this->assertSignedImageUrl($srcset[1][0], 'img/photo.jpg', ['w' => 600, 'h' => 400, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_src_is_the_first_srcset_url_and_width_and_height_attributes_are_set()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertEquals($srcset[0][0], $this->attribute($html, 'img', 'src'));
		$this->assertEquals('300', $this->attribute($html, 'img', 'width'));
		$this->assertEquals('200', $this->attribute($html, 'img', 'height'));
	}

	function test_the_img_fallback_uses_the_last_configured_format()
	{
		Config::set('bladeimagecrop.formats', ['avif', 'webp', 'png']);

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertSignedImageUrl(
			$this->attribute($html, 'img', 'src'),
			'img/photo.jpg',
			['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'png']
		);
	}

	function test_focal_point_properties_become_the_glide_crop_position()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200, 75, 25]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertSignedImageUrl($srcset[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-75-25', 'fm' => 'jpg']);
	}

	function test_horizontal_focal_point_alone_keeps_the_default_vertical_offset()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200, 75]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertSignedImageUrl($srcset[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-75-50', 'fm' => 'jpg']);
	}

	function test_configured_default_offsets_are_used()
	{
		Config::set('bladeimagecrop.offset_x', 40);
		Config::set('bladeimagecrop.offset_y', 70);

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertSignedImageUrl($srcset[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-40-70', 'fm' => 'jpg']);
	}

	function test_configured_pixel_ratios_are_all_output()
	{
		Config::set('bladeimagecrop.pixel_device_ratios', ['1x', '2x', '4x']);

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertEquals(['1x', '2x', '4x'], array_column($srcset, 1));
		$this->assertSignedImageUrl($srcset[2][0], 'img/photo.jpg', ['w' => 1200, 'h' => 800, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_multiple_sizes_use_width_descriptors_and_a_bare_width_follows_the_previous_aspect_ratio()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[[300, 180], [1024, 300], 2048]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertEquals(['300w', '1024w', '2048w'], array_column($srcset, 1));
		$this->assertSignedImageUrl($srcset[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 180, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
		$this->assertSignedImageUrl($srcset[1][0], 'img/photo.jpg', ['w' => 1024, 'h' => 300, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
		$this->assertSignedImageUrl($srcset[2][0], 'img/photo.jpg', ['w' => 2048, 'h' => 600, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_a_bare_width_keeps_the_previous_focal_point()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[[300, 200, 75], 400]"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertSignedImageUrl($srcset[1][0], 'img/photo.jpg', ['w' => 400, 'h' => 267, 'fit' => 'crop-75-50', 'fm' => 'jpg']);
	}

	// The README promises width-only images are resized without trimming the height, so no crop is requested
	function test_width_only_resizes_without_cropping()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" width="320"/>');

		[$srcset] = $this->srcsets($html);

		$this->assertSignedImageUrl($srcset[0][0], 'img/photo.jpg', ['w' => 320, 'fm' => 'jpg']);
		$this->assertSignedImageUrl($srcset[1][0], 'img/photo.jpg', ['w' => 640, 'fm' => 'jpg']);
		$this->assertEquals('320', $this->attribute($html, 'img', 'width'));
	}

	function test_width_only_sets_a_height_attribute_matching_the_original_aspect()
	{
		$this->markTestIncomplete(
			'Decide how the height attribute is found for width-only images without reading the original at render time '
			. '(e.g. a dimensions cache filled by the image route, or reading the local source disk only).'
		);
	}

	function test_full_urls_on_this_host_and_relative_paths_point_at_the_same_source()
	{
		$fromPath = (string) $this->blade('<x-img src="img/photo.jpg" :properties="[300, 200]"/>');
		$fromUrl = (string) $this->blade('<x-img src="http://localhost/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertSignedImageUrl($this->srcsets($fromPath)[0][0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
		$this->assertSignedImageUrl($this->srcsets($fromUrl)[0][0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);
	}

	function test_class_and_alt_are_passed_through_to_the_img_tag()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]" class="m-1" alt="Description of Image"/>');

		$this->assertEquals('m-1', $this->attribute($html, 'img', 'class'));
		$this->assertEquals('Description of Image', $this->attribute($html, 'img', 'alt'));
	}

	function test_sources_false_leaves_out_the_srcset()
	{
		$html = (string) $this->blade('<x-img sources="false" src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertStringNotContainsString('srcset=', $html);
		$this->assertSignedImageUrl(
			$this->attribute($html, 'img', 'src'),
			'img/photo.jpg',
			['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']
		);
	}

	function test_disabled_outputs_the_original_image()
	{
		Config::set('bladeimagecrop.enabled', false);

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertEquals('/img/photo.jpg', $this->attribute($html, 'img', 'src'));
		$this->assertStringNotContainsString('/bic/', $html);
	}

	function test_properties_without_a_colon_prefix_throw()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Properties must use : as a prefix.');

		$this->blade('<x-img src="/img/photo.jpg" properties="[300, 200]"/>');
	}
}
