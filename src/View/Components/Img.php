<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\Background;
use DNABeast\BladeImageCrop\HoldImage;
use DNABeast\BladeImageCrop\ImageProps;
use DNABeast\BladeImageCrop\Source;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;


class Img extends Component
{
	public $src;
	public $width;
	public $properties;
	public $sources;


	public function __construct($src, $width=null, $properties=null, $sources=null)
	{
		$this->image = new HoldImage($src);
		$this->sources = $sources=='false'?false:true;
		$this->properties = $properties??$width;
		$this->imageFormats = collect(array_keys(config('bladeimagecrop.build_classes')));
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\Contracts\View\View|\Closure|string
	 */
	public function render()
	{

		return function (array $data){

			return <<<EOT
			<img {$this->build()['sources']} {$this->build()['background']} src="{$this->build()['src']}" width="{$this->calculatedProperties()[0][0]}" height="{$this->calculatedProperties()[0][1]}" {$data['attributes']}>
			EOT;
		};


	}


	public function build(){

		$format = config('bladeimagecrop.build_classes');

		$options = [
			'src' => $this->image->file(),
			'format' => array_keys(config('bladeimagecrop.build_classes'))[count(config('bladeimagecrop.build_classes'))-1],
			'properties' => $this->calculatedProperties(),
			'pixelRatios' => isset($this->properties[0][1])?false:true
		];

		$defaultImageSrc = explode(" ", Source::make($options)->srcsetLines())[0];

		if (config('bladeimagecrop.backgrounds')){
			$backgroundString = (new Background($defaultImageSrc))->render();
		}

		$sourcesString = $this->sources?'srcset="'.Source::make($options)->srcsetLines().'"':'';

		return [
			'sources' => $sourcesString,
			'background' => $backgroundString??null,
			'src' => $defaultImageSrc,
			'attributes' => 'class="" alt=""'
		];

	}


	public function calculatedProperties(){
		if (!$this->properties && !$this->width){
			throw new \Exception("No Width provided");
		}
		$aspect = is_string($this->properties)||!isset($this->properties[1])?$this->aspectFromImage():null;

		return (new ImageProps)->calc($this->properties, $aspect);
	}

	public function aspectFromImage(){
		try {
			$originalImage = getimagesize( $this->image->path() );
			return $originalImage[1]/$originalImage[0];
		} catch (\Exception $e) {
			return 3/4; // default failed image shape
		}
	}

}
