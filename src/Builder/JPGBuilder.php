<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;

class JPGBuilder extends ImageTypeBuilder
{

	public function create($image, $uri){

		ob_start();
			imageJpeg($image, null, 70);
			$data = ob_get_contents();
		ob_end_clean();

		Storage::disk( config('bladeimagecrop.disk') )->put($uri, $data);

	}

}
