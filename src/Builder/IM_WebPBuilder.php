<?php

namespace DNABeast\BladeImageCrop\Builder;

use DNABeast\BladeImageCrop\TempStorage;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Log;

class IM_WebPBuilder extends ImageTypeBuilder
{
	public $originUrl;
	public $image;
	public $tempFilePath;

	public function __construct($originUrl)
	{
		$this->originUrl = $originUrl;
		$this->image = $this->makeImage();
		$this->tempFilePath = null;
	}

	public function makeImage(){
		$this->tempFilePath = TempStorage::fire( $this->originUrl );
			return new Imagick($this->tempFilePath);
	}

	public function resize($options){
		if ($this->image->getNumberImages() == 0){
			Log::info('resizing image is somehow missing. '. $this->originUrl);
			return null;
		}
		$this->image->autoOrient();
		$this->image->cropImage( $options['cropWidth'], $options['cropHeight'], $options['x'], $options['y'] );
		$this->image->resizeImage( $options['targetWidth'], $options['targetHeight'], 7, 1 );
		return $this;
	}

	public function save($destinationPath){
		if ($this->image->getNumberImages() == 0){
			Log::info('Saving image is somehow missing. '. $this->originUrl);
			return null;
		}
		$this->image->setImageFormat( 'webp');
		$this->image->setImageCompressionQuality( 80 );

		$processedPath = tempnam(sys_get_temp_dir(), 'img_pros_');

		$this->image->writeImage($processedPath);

		Storage::disk( config('bladeimagecrop.disk') )->put($destinationPath, fopen($processedPath, 'r+'));

		$this->image->clear();
		$this->image->destroy(); // Depreciated in later versions of Imagick
		unset($this->image);
		if ($this->tempFilePath && file_exists($this->tempFilePath)){
			@unlink($this->tempFilePath);
		}
		if ( file_exists($processedPath)){
			@unlink($processedPath);
		}
		gc_collect_cycles();
	}

}
