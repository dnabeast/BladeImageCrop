<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\UriHelper;
use Illuminate\View\Component;


class Pic extends Component
{
	public $src;
	public $width;
	public $properties;


	public function __construct($src, $width=null, $properties=null)
	{
		$this->uri = new UriHelper;
		$this->uri->trim($src);
		$this->src = trim($src, "/");
		$this->properties = $properties??$width;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\Contracts\View\View|\Closure|string
	 */
	public function render()
	{
		return function (array $data){
			$attributes = $data['attributes']->toHtml();
			$propertyString = is_string($this->properties)?$this->properties:"[".implode(",", $this->properties)."]";
			return <<<blade
				<x-sources src="$this->src" :properties="$propertyString" />
				<x-img sources="false" src="$this->src" :properties="$propertyString" $attributes />
			blade;
		};

	}

}
