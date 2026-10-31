<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\GlideUrls;
use DNABeast\BladeImageCrop\ImageProps;
use DNABeast\BladeImageCrop\Background;
use DNABeast\BladeImageCrop\SourceDimensions;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Img extends Component implements Htmlable
{
	public $src;
	public $width;
	public $properties;
	public $sources;

	public function __construct($src, $width=null, $properties=null, $sources=null)
	{
		$this->src = $src;
		$this->sources = $sources=='false'?false:true;
		$this->properties = $properties??$width;
	}

	/**
	 * Get the view / contents that represent the component.
	 * The component is its own Htmlable as a returned string would be compiled as Blade, including any alt text or src inside it.
	 *
	 * @return \Illuminate\Contracts\Support\Htmlable
	 */
	public function render()
	{
		return $this;
	}

	public function toHtml()
	{
		if (!config('bladeimagecrop.enabled', true)) {
			return $this->originalImg(GlideUrls::original($this->src));
		}

		$urls = new GlideUrls($this->src);

		if (!$urls->isLocal()) {
			return $this->originalImg($this->src);
		}

		$lines = $this->calculatedProperties();
		$fallbackFormat = collect(config('bladeimagecrop.formats'))->last();

		$src = $urls->url($lines[0], $fallbackFormat);
		$srcset = $this->sources
			? 'srcset="'.e($urls->srcset($lines, $this->hasManySizes(), $fallbackFormat)).'" '
			: '';
		// Width only images take their height from the original's aspect, left off when the original can't be measured
		$heightValue = $lines[0][1] ?: (new SourceDimensions)->heightFor($urls->sourcePath, $lines[0][0], $urls->source);
		$height = $heightValue ? ' height="'.$heightValue.'"' : '';

		$background = config('bladeimagecrop.backgrounds') ? (new Background($src))->render().' ' : '';

		return '<img '.$srcset.$background.'src="'.e($src).'" width="'.$lines[0][0].'"'.$height.' '.$this->attributes.'>';
	}

	/**
	 * An uncropped image. The size goes in attributes so the site's css still wins, object-fit stops them stretching the original.
	 */
	protected function originalImg($src)
	{
		$firstLine = $this->properties ? (new ImageProps)->properWrapping($this->properties)[0] : [];
		$width = (int) ($firstLine[0] ?? 0);
		$height = (int) ($firstLine[1] ?? 0);

		$size = ($width ? ' width="'.$width.'"' : '').($height ? ' height="'.$height.'"' : '');

		return '<img src="'.e($src).'"'.$size.' '.$this->attributes->merge(['style' => 'object-fit: cover']).'>';
	}

	public function calculatedProperties(){
		if (!$this->properties && !$this->width){
			throw new \Exception("No Width provided");
		}

		return (new ImageProps)->calc($this->properties);
	}

	public function hasManySizes(){
		return count((new ImageProps)->properWrapping($this->properties)) > 1;
	}


}
