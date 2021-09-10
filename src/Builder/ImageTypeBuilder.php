<?php

namespace DNABeast\BladeImageCrop\Builder;

use DNABeast\BladeImageCrop\UriHelper;

abstract class ImageTypeBuilder
{

	public function __construct()
	{
		$this->uri = new UriHelper;
	}

	abstract public function create($image, $src);

}
