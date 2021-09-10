<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\ImageProps;
use DNABeast\BladeImageCrop\Source;
use DNABeast\BladeImageCrop\UriHelper;
use Illuminate\View\Component;


class Sources extends Component
{
	public $src;
	public $media;
	public $properties;
	public $sizes;

	public function __construct($src, $properties, $media = null,  $sizes = null)
	{
		$this->uri = new UriHelper;
		$this->src = $this->uri->trim($src);
		$this->media = $media;
		$this->properties = $properties;
		$this->sizes = $sizes;
		$this->imageFormats = collect(array_keys(config('bladeimagecrop.build_classes')));

	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\Contracts\View\View|\Closure|string
	 */
	public function render()
	{
		return $this->build();
	}


	public function build(){
		return function (array $data){

			return $this->imageFormats->map(function($format) use ($data){
				$options = [
					'src' => $this->src,
					'media' => $this->media,
					'format' => $format,
					'properties' => $this->calculatedProperties(),
					'sizes' => $this->sizes,
					'pixelRatios' => isset($this->properties[0][1])?false:true,
					'attributes' => $data['attributes']
				];

				return Source::make($options)->render();
			})->implode("\n");

		};
	}

	public function calculatedProperties(){
		$aspect = is_string($this->properties)||!isset($this->properties[1])?$this->aspectFromImage():null;
		return (new ImageProps)->calc($this->properties, $aspect);
	}

	public function aspectFromImage(){
		$originalImage = getimagesize( $this->uri->path( $this->src ) );
		return round($originalImage[1]/$originalImage[0], 2);
	}

}
