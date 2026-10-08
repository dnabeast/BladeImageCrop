<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;
use Imagick;
use Log;

class IM_WebPBuilder extends ImageTypeBuilder
{
	public $originPath;
    public $image;

	public function __construct($originPath)
	{
		$this->originPath = $originPath;
		$this->image = $this->makeImage();
	}

	public function makeImage(){
		Log::info('Trying to make. '. $this->originPath);;
		$image = new Imagick($this->originPath);
		return $image;
	}

	public function resize($options){
		if ($this->image->getNumberImages() == 0){
			Log::info('Image is somehow missing. '. $this->originPath);;
			return null;
		}
		$this->image->autoOrient();
		$this->image->cropImage( $options['cropWidth'], $options['cropHeight'], $options['x'], $options['y'] );
		$this->image->resizeImage( $options['targetWidth'], $options['targetHeight'], 7, 1 );
		return $this;
	}

	public function save($destinationPath){
		if ($this->image->getNumberImages() == 0){
			Log::info('Image is somehow missing. '. $this->originPath);;
			return null;
		}
		$this->image->setImageFormat( 'webp');
		$this->image->setImageCompressionQuality( 80 );
		Storage::disk( config('bladeimagecrop.disk') )->put($destinationPath, $this->image);
		$this->image->clear();
	}

}
