<?php

namespace DNABeast\BladeImageCrop\View\Components;

use DNABeast\BladeImageCrop\GlideUrls;
use DNABeast\BladeImageCrop\ImageProps;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;


class Sources extends Component implements Htmlable
{
	public $src;
	public $media;
	public $properties;
	public $sizes;

	public function __construct($src, $properties, $media = null,  $sizes = null)
	{
		$this->src = $src;
		$this->media = $media;
		$this->properties = $properties;
		$this->sizes = $sizes;
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
		if (!config('bladeimagecrop.enabled', true)) {
			return $this->originalSource(GlideUrls::original($this->src));
		}

		$urls = new GlideUrls($this->src);

		if (!$urls->isLocal()) {
			return $this->originalSource($this->src);
		}

		$lines = (new ImageProps)->calc($this->properties);
		$manySizes = count((new ImageProps)->properWrapping($this->properties)) > 1;

		$media = $this->media ? ' media="'.e($this->media).'"' : '';
		$sizes = $this->sizes ? ' sizes="'.e($this->sizes).'"' : '';

		return collect(config('bladeimagecrop.formats'))->map(function ($format) use ($urls, $lines, $manySizes, $media, $sizes){
			$srcset = $urls->srcset($lines, $manySizes, $format);

			return '<source type="'.GlideUrls::mimeType($format).'" srcset="'.e($srcset).'"'.$media.$sizes.' '.$this->attributes.'>';
		})->implode("\n");
	}

	/**
	 * An uncropped source, only needed when a media query picks a different image from the img tag
	 */
	protected function originalSource($src)
	{
		if (!$this->media) {
			return '';
		}

		return '<source srcset="'.e($src).'" media="'.e($this->media).'" '.$this->attributes.'>';
	}

}
