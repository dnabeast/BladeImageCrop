<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;

class WebPBuilder extends ImageTypeBuilder
{

	public function create($image, $src){

		ob_start();
			imagewebp($image, null, 75);
			$data = ob_get_contents();
		ob_end_clean();

		Storage::disk( config('bladeimagecrop.disk') )->put($src, $data);

	}

}
