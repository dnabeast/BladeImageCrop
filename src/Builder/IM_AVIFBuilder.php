<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;
use Imagick;

class IM_AVIFBuilder extends ImageTypeBuilder
{
	public $imageString;

	public function __construct($imageString)
	{
		$this->imageString = $imageString;
		$this->image = $this->makeImage();
	}

	public function makeImage(){
		$image = (new Imagick);
		$image->readImageBlob($this->imageString);
		return $image;
	}

	public function resize($options){
		$this->image->cropImage( $options['cropWidth'], $options['cropHeight'], $options['x'], $options['y'] );
		$this->image->resizeImage( $options['targetWidth'], $options['targetHeight'], 7, 1 );
		return $this;
	}

	public function save($path){
		$this->image->setImageFormat( 'avif' );
		$this->image->setCompressionQuality( 60 );
		return Storage::disk( config('bladeimagecrop.disk') )->put($path, $this->image);
	}

}
