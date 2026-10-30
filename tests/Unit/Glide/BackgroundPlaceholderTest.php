<?php

namespace DNABeast\BladeImageCrop\Tests\Unit\Glide;

use DNABeast\BladeImageCrop\BGBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class BackgroundPlaceholderTest extends GlideTestCase
{
	public function setUp(): void
	{
		parent::setUp();

		Config::set('bladeimagecrop.backgrounds', true);
		Config::set('bladeimagecrop.background_builder', BGBuilder::class);
	}

	function test_img_gets_an_inline_placeholder_background()
	{
		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertStringContainsString("background-image:linear-gradient(#00C4FCbb, transparent 55%), url('data:image/png;base64,", $this->attribute($html, 'img', 'style'));
	}

	function test_pic_img_gets_the_placeholder_but_sources_do_not()
	{
		$html = (string) $this->blade('<picture><x-pic src="/img/photo.jpg" :properties="[300, 200]"/></picture>');

		preg_match_all('/<source\b[^>]*>/', $html, $sources);
		foreach ($sources[0] as $source) {
			$this->assertStringNotContainsString('style=', $source);
		}
		$this->assertNotNull($this->attribute($html, 'img', 'style'));
	}

	function test_the_placeholder_does_not_touch_the_source_or_cache_disks()
	{
		$this->blade('<x-img src="/img/missing.jpg" :properties="[300, 200]"/>');

		$this->assertEmpty(Storage::disk('bic-source')->allFiles());
		$this->assertEmpty(Storage::disk('bic-cache')->allFiles());
	}

	function test_backgrounds_false_leaves_it_off()
	{
		Config::set('bladeimagecrop.backgrounds', false);

		$html = (string) $this->blade('<x-img src="/img/photo.jpg" :properties="[300, 200]"/>');

		$this->assertStringNotContainsString('style=', $html);
	}
}
