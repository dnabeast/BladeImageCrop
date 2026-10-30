<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\GlideUrls;
use DNABeast\BladeImageCrop\ImageProps;
use DNABeast\BladeImageCrop\Background;
use DNABeast\BladeImageCrop\SourceDimensions;
use Illuminate\View\Component;

class Img extends Component
{
	public $src;
	public $width;
	public $properties;
	public $sources;
	public $urls;

	public function __construct($src, $width=null, $properties=null, $sources=null)
	{
		$this->src = $src;
		$this->urls = new GlideUrls($src);
		$this->sources = $sources=='false'?false:true;
		$this->properties = $properties??$width;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\Contracts\View\View|\Closure|string
	 */
	public function render()
	{
		if (!config('bladeimagecrop.enabled', true) || !$this->urls->isLocal()){
			$propertyArray = is_array($this->properties)?['width'=>$this->properties[0].'px', 'height'=>isset($this->properties[1])?$this->properties[1].'px':'auto']:['width' => $this->properties.'px', 'height'=>'auto'];
			return '<img style="object-fit: cover; width: '.$propertyArray['width'].'; height: '.$propertyArray['height'].';" src="'.e($this->src).'" width="'.$propertyArray['width'].'" height="'.$propertyArray['height'].'">';
		}

		return function (array $data){
			$lines = $this->calculatedProperties();
			$fallbackFormat = collect(config('bladeimagecrop.formats'))->last();

			$src = $this->urls->url($lines[0], $fallbackFormat);
			$srcset = $this->sources
				? 'srcset="'.e($this->urls->srcset($lines, $this->hasManySizes(), $fallbackFormat)).'" '
				: '';
			// Width only images take their height from the original's aspect, left off when the original can't be measured
			$heightValue = $lines[0][1] ?: (new SourceDimensions)->heightFor($this->urls->sourcePath, $lines[0][0]);
			$height = $heightValue ? ' height="'.$heightValue.'"' : '';

			$background = config('bladeimagecrop.backgrounds') ? (new Background($src))->render().' ' : '';

			return '<img '.$srcset.$background.'src="'.e($src).'" width="'.$lines[0][0].'"'.$height.' '.$data['attributes'].'>';
		};

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
