<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Str;

class HoldImage
{
	public \Illuminate\Support\Stringable $src;

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
