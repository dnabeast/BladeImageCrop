<?php

namespace DNABeast\BladeImageCrop\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;


class Pic extends Component implements Htmlable
{
	public $src;
	public $width;
	public $properties;

	public function __construct($src, $width=null, $properties=null)
	{
		$this->src = $src;
		$properties = $properties??$width;
		// properties="[300, 200]" has always worked on this tag, unlike x-img and x-sources
		$this->properties = is_string($properties) ? (json_decode($properties, true) ?? $properties) : $properties;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\Contracts\Support\Htmlable
	 */
	public function render()
	{
		return $this;
	}

	public function toHtml()
	{
		$sources = (new Sources($this->src, $this->properties))->withAttributes([]);
		$img = (new Img($this->src, null, $this->properties, 'false'))->withAttributes($this->attributes->getAttributes());

		return $sources->toHtml()."\n".$img->toHtml();
	}

}
