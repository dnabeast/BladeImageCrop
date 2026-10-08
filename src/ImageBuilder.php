<?php

namespace DNABeast\BladeImageCrop;


class ImageBuilder
{
	public $path;
	public $format;
    public $class;

	public function __construct($path, $format)
	{
		\Log::info('building '.$path);
		$this->path = $path;
		$this->format = $format;
		$this->class = $this->buildClass();
	}

	public function buildClass(){
		$class = config('bladeimagecrop.build_classes')[$this->format];
		return new $class($this->path);
	}

	public function resize($options){
		$this->class->resize($options);
		return $this;
	}

	public function save($uri){
		$this->class->save($uri);
	}

}
