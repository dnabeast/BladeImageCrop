<?php

namespace DNABeast\BladeImageCrop;


class ImageBuilder
{
	public $originUrl;
	public $format;
    public $class;

	public function __construct($originUrl, $format)
	{
		$this->originUrl = $originUrl;
		$this->format = $format;
		$this->class = $this->buildClass();
	}

	public function buildClass(){
		$class = config('bladeimagecrop.build_classes')[$this->format];
		return new $class($this->originUrl);
	}

	public function resize($options){
		$this->class->resize($options);
		return $this;
	}

	public function save($targetUrl){
		$this->class->save($targetUrl);
	}

}
