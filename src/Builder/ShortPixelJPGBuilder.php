<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;
use ShortPixel;

class ShortPixelJPGBuilder extends ImageTypeBuilder
{

	public function create($image, $uri){

		ob_start();
			imagebmp($image);
			$data = ob_get_contents();
		ob_end_clean();


		ShortPixel\setKey( config('shortpixel.api_key') );
		ShortPixel\fromBuffer( $this->uri->filename($uri).'.jpg', $data)->wait(300)->toFiles( $this->uri->directory($uri) );

	}

}
