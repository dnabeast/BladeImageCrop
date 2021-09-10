<?php

namespace DNABeast\BladeImageCrop;


class Background
{
	public $src;

	public function __construct($src)
	{
		$this->src = $src;
	}

	public function render(){
		$builder = config('bladeimagecrop.background_builder');
		return (new $builder($this->src))->make();
	}
}
