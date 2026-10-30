<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\GlideUrls;
use DNABeast\BladeImageCrop\ImageProps;
use Illuminate\View\Component;


class Sources extends Component
{
	public $src;
	public $media;
	public $properties;
	public $sizes;
	public $urls;

	public function __construct($src, $properties, $media = null,  $sizes = null)
	{
		$this->src = $src;
		$this->urls = new GlideUrls($src);
		$this->media = $media;
		$this->properties = $properties;
		$this->sizes = $sizes;
	}

	/**
	 * Get the view / contents that represent the component.
	 *
	 * @return \Illuminate\Contracts\View\View|\Closure|string
	 */
	public function render()
	{
		if (!config('bladeimagecrop.enabled', true) || !$this->urls->isLocal()){
			return '';
		}

		return function (array $data){
			$lines = (new ImageProps)->calc($this->properties);
			$manySizes = count((new ImageProps)->properWrapping($this->properties)) > 1;

			$media = $this->media ? ' media="'.e($this->media).'"' : '';
			$sizes = $this->sizes ? ' sizes="'.e($this->sizes).'"' : '';

			return collect(config('bladeimagecrop.formats'))->map(function ($format) use ($lines, $manySizes, $media, $sizes, $data){
				$srcset = $this->urls->srcset($lines, $manySizes, $format);

				return '<source type="'.GlideUrls::mimeType($format).'" srcset="'.e($srcset).'"'.$media.$sizes.' '.$data['attributes'].'>';
			})->implode("\n");
		};

	}

}