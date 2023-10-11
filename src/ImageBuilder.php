<?php

namespace DNABeast\BladeImageCrop;


class ImageBuilder
{
	public $glob;
	public $format;
    public $class;

	public function __construct($glob, $format)
	{
		$this->glob = $glob;
		$this->format = $format;
		$this->class = $this->buildClass();
	}

	public function buildClass(){
		$class = config('bladeimagecrop.build_classes')[$this->format];
		return new $class($this->glob);
	}

	public function resize($options){
		$this->class->resize($options);
		return $this;
	}

	public function save($uri){
		$this->class->save($uri);
	}

	// public function create($image, $uri, $format){
	// 	$class = config('bladeimagecrop.build_classes')[$format];
	// 	( new $class )->create($image, $uri);
	// }

}
