<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class PicAndSourcesComponentTest extends GlideTestCase
{

	function test_pic_outputs_a_source_per_format_in_order_then_the_img()
	{
		$html = (string) $this->blade('<picture><x-pic src="/img/photo.jpg" :properties="[300, 200]"/></picture>');

		preg_match_all('/<source\b[^>]*type="([^"]*)"/', $html, $types);
		$this->assertEquals(['image/webp', 'image/jpeg'], $types[1]);

		[$webp, $jpg] = $this->srcsets($html);

		$this->assertSignedImageUrl($webp[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'webp']);
		$this->assertSignedImageUrl($webp[1][0], 'img/photo.jpg', ['w' => 600, 'h' => 400, 'fit' => 'crop-50-50', 'fm' => 'webp']);
		$this->assertSignedImageUrl($jpg[0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'jpg']);

		$this->assertLessThan(strpos($html, '<img'), strrpos($html, '<source'));
	}

	function test_pic_img_has_no_srcset_of_its_own_and_keeps_attributes()
	{
		$html = (string) $this->blade('<picture><x-pic src="/img/photo.jpg" :properties="[300, 200]" class="m-1" alt="Description of Image"/></picture>');

		preg_match('/<img\b[^>]*>/', $html, $img);

		$this->assertStringNotContainsString('srcset=', $img[0]);
		$this->assertEquals('m-1', $this->attribute($html, 'img', 'class'));
		$this->assertEquals('Description of Image', $this->attribute($html, 'img', 'alt'));
		$this->assertEquals('300', $this->attribute($html, 'img', 'width'));
		$this->assertEquals('200', $this->attribute($html, 'img', 'height'));
	}

	function test_formats_config_controls_the_sources()
	{
		Config::set('bladeimagecrop.formats', ['avif', 'webp', 'jpg']);

		$html = (string) $this->blade('<x-sources src="/img/photo.jpg" :properties="[300, 200]"/>');

		preg_match_all('/<source\b[^>]*type="([^"]*)"/', $html, $types);
		$this->assertEquals(['image/avif', 'image/webp', 'image/jpeg'], $types[1]);

		$this->assertSignedImageUrl($this->srcsets($html)[0][0][0], 'img/photo.jpg', ['w' => 300, 'h' => 200, 'fit' => 'crop-50-50', 'fm' => 'avif']);
	}

	function test_sources_pass_through_media_and_sizes()
	{
		$html = (string) $this->blade('<x-sources src="/img/photo.jpg" :properties="[[800, 600], 1024]" media="(max-width: 450px)" sizes="(min-width: 60rem) 80vw, 100vw"/>');

		preg_match_all('/<source\b[^>]*>/', $html, $sources);
		$this->assertCount(2, $sources[0]);

		foreach ($sources[0] as $source) {
			$this->assertStringContainsString('media="(max-width: 450px)"', $source);
			$this->assertStringContainsString('sizes="(min-width: 60rem) 80vw, 100vw"', $source);
		}

		[$webp] = $this->srcsets($html);
		$this->assertEquals(['800w', '1024w'], array_column($webp, 1));
		$this->assertSignedImageUrl($webp[1][0], 'img/photo.jpg', ['w' => 1024, 'h' => 768, 'fit' => 'crop-50-50', 'fm' => 'webp']);
	}

	function test_mobile_and_desktop_sources_can_use_different_crops()
	{
		$html = (string) $this->blade(
			'<picture>'
			. '<x-sources src="/img/photo.jpg" :properties="[300, 300, 35, 50]" media="(max-width: 450px)"/>'
			. '<x-sources src="/img/photo.jpg" :properties="[800, 600]"/>'
			. '<x-img sources="false" src="/img/photo.jpg" :properties="[800, 600]"/>'
			. '</picture>'
		);

		$srcsets = $this->srcsets($html);
		$this->assertCount(4, $srcsets);

		$this->assertSignedImageUrl($srcsets[0][0][0], 'img/photo.jpg', ['w' => 300, 'h' => 300, 'fit' => 'crop-35-50', 'fm' => 'webp']);
		$this->assertSignedImageUrl($srcsets[2][0][0], 'img/photo.jpg', ['w' => 800, 'h' => 600, 'fit' => 'crop-50-50', 'fm' => 'webp']);
	}

	function test_sources_output_nothing_when_disabled()
	{
		Config::set('bladeimagecrop.enabled', false);

		$html = (string) $this->blade('<x-sources src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertStringNotContainsString('<source', $html);
	}

	function test_sources_keep_their_media_query_when_disabled()
	{
		Config::set('bladeimagecrop.enabled', false);

		$html = (string) $this->blade('<x-sources src="/img/wide.jpg" media="(min-width: 800px)" :properties="[800, 300]"/>');

		$this->assertEquals('(min-width: 800px)', $this->attribute($html, 'source', 'media'));
		$this->assertEquals(Storage::disk('bic-source')->url('img/wide.jpg'), $this->attribute($html, 'source', 'srcset'));
	}

	function test_sources_render_when_disabled_and_the_source_disk_is_broken()
	{
		Config::set('bladeimagecrop.enabled', false);
		Config::set('bladeimagecrop.source_disk', 'not-a-disk');

		$html = (string) $this->blade('<picture><x-pic src="/img/photo.jpg" :properties="[300, 200]"/></picture>');

		$this->assertEquals('/img/photo.jpg', $this->attribute($html, 'img', 'src'));
	}

	function test_pic_accepts_any_width_and_a_quote_in_the_src()
	{
		Config::set('bladeimagecrop.enabled', false);

		$html = (string) $this->blade('<picture><x-pic :src="$src" width="100%"/></picture>', ['src' => 'https://other.test/a"b.jpg']);

		$this->assertEquals('100', $this->attribute($html, 'img', 'width'));
		$this->assertStringContainsString('a&quot;b.jpg', $html);
	}

	function test_pic_still_accepts_properties_without_a_colon()
	{
		$html = (string) $this->blade('<picture><x-pic src="/img/photo.jpg" properties="[300, 200]"/></picture>');

		$this->assertEquals('200', $this->attribute($html, 'img', 'height'));
	}

	function test_pic_attributes_are_only_escaped_once()
	{
		$html = (string) $this->blade('<picture><x-pic src="/img/photo.jpg" :properties="[300, 200]" alt="Bob\'s photo"/></picture>');

		$this->assertEquals("Bob's photo", $this->attribute($html, 'img', 'alt'));
	}
}
