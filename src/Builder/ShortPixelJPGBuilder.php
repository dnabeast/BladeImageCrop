<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;
use ShortPixel;
use Str;

class ShortPixelJPGBuilder extends ImageTypeBuilder
{

	public $imageString;
    public $image;
    public $resize;

	public function __construct($imageString)
	{
		$this->imageString = $imageString;
		$this->image = $this->makeImage();
		$this->resize = null;
	}

	public function makeImage(){
		return imagecreatefromstring($this->imageString);
	}

	public function resize($options){
		$cropOptions = [
			'x' => $options['x'],
			'y' => $options['y'],
			'width' => $options['cropWidth'],
			'height' => $options['cropHeight'],
		];

		$this->image = imagecrop($this->image, $cropOptions);

		$this->resize = [ $options['targetWidth'], $options['targetHeight'] ];
	}

	public function save($path){
		ob_start();
			imagebmp($this->image, null, 75);
			$data = ob_get_contents();
		ob_end_clean();

		ShortPixel\setKey( config('shortpixel.api_key') );

		$pathPieces = Str::of(Storage::disk( config('bladeimagecrop.disk') )->path(trim($path, '/')))->explode('/');
		$filename = $pathPieces->pop();
		$directory = $pathPieces->implode('/');

		$shortPixel = ShortPixel\fromBuffer( $filename, $data);

		if ($this->resize){
			$shortPixel->resize(...$this->resize);
		}

		$shortPixel->wait(300)->toFiles( $directory );

	}
}

