<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Log;


class HoldImage
{
	public $src;

	public function __construct($src)
	{
		$this->src = Str::of($src);
	}

	public function url()
	{
		if ($this->src->doesntStartWith('http')){
			return url($this->src);
		}
		return $this->src;
	}

}
