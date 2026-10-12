<?php

namespace DNABeast\BladeImageCrop\Builder;

use DNABeast\BladeImageCrop\Helper;
use DNABeast\BladeImageCrop\TempStorage;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Log;

class IM_AVIFBuilder extends ImageTypeBuilder
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
		Log::info('Trying to make. '. $this->originUrl);
		$this->tempFilePath = TempStorage::fire( $this->originUrl );
		Log::info('Temp Path. '. $this->tempFilePath);
		return new Imagick($this->tempFilePath);
	}

	public function resize($options){
		Log::info('Trying to resize. '. $this->originUrl);

		if ($this->image->getNumberImages() == 0){
			Log::info('Image is somehow missing. '. $this->originUrl);
			return null;
		}
		$this->image->autoOrient();
		$this->image->cropImage( $options['cropWidth'], $options['cropHeight'], $options['x'], $options['y'] );
		$this->image->resizeImage( $options['targetWidth'], $options['targetHeight'], 7, 1 );
		return $this;
	}

	public function save($destinationPath){
		Log::info('Trying to save. '. $this->originUrl);

		if ($this->image->getNumberImages() == 0){
			Log::info('Image is somehow missing. '. $this->originUrl);
			return null;
		}
		$this->image->setImageFormat('avif');
		$this->image->setCompressionQuality( 70 );

		Storage::disk( config('bladeimagecrop.disk') )->put($destinationPath, $this->image);
		$this->image->clear();
		if ($this->tempFilePath && file_exists($this->tempFilePath)) {
			unlink($this->tempFilePath);
		}
	}

}
