<?php

namespace DNABeast\BladeImageCrop;


class ImageBuilder
{

	public function create($image, $uri, $format){
		$class = config('bladeimagecrop.build_classes')[$format];
		( new $class )->create($image, $uri);
	}

}
