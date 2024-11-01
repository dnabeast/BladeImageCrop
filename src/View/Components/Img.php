<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\Background;
use DNABeast\BladeImageCrop\HoldImage;
use DNABeast\BladeImageCrop\ImageProps;
use DNABeast\BladeImageCrop\Source;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\Component;


class Img extends Component
{
	public $src;
	public $width;
	public $properties;
	public $sources;
    public $image;
    public $imageFormats;

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
		if (!config('bladeimagecrop.enabled', true)){
			return '<img src="'.$this->image->file().'" />';
		}


		return function (array $data){
            $build = $this->build();
			return <<<EOT
			<img {$build['sources']} {$build['background']} src="{$build['src']}" width="{$this->calculatedProperties()[0][0]}" height="{$this->calculatedProperties()[0][1]}" {$data['attributes']}>
			EOT;
		};

	}


	public function build(){

		$options = [
			'src' => $this->image->file(),
			'format' => array_keys(config('bladeimagecrop.build_classes'))[count(config('bladeimagecrop.build_classes'))-1],
			'properties' => $this->calculatedProperties(),
			'pixelRatios' => isset($this->properties[0][1])?false:true
		];

        $lines = Source::make($options)->srcsetLines();

		$defaultImageSrc = explode(" ", $lines)[0];

		if (config('bladeimagecrop.backgrounds')){
            $backgroundLocation = 'blade_image_crop_holding/'.Str::of($defaultImageSrc)->after('blade_image_crop_holding');
            $backgroundString = (new Background($backgroundLocation))->render();
		}

		$sourcesString = $this->sources?'srcset="'.$lines.'"':'';

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
