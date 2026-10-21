<?php

namespace DNABeast\BladeImageCrop\Tests\Unit;

use DNABeast\BladeImageCrop\HoldImage;
use DNABeast\BladeImageCrop\View\Components\Img;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;



class HoldImageTest extends TestCase
{
    public function test_it_handles_a_full_url_correctly(): void
    {
        $fullUrl = 'http://localhost/image.jpg';

		$holdImage = new HoldImage($fullUrl);

        $this->assertEquals($fullUrl, $holdImage->url());

    }

    public function test_it_handles_a_local_path_correctly(): void
    {
        $localPath = 'images/sample.jpg';
		$fullUrl = 'http://localhost/images/sample.jpg';

		$holdImage = new HoldImage($localPath);

		$this->assertEquals($fullUrl, $holdImage->url());
    }
}
