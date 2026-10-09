<?php

namespace DNABeast\BladeImageCrop\Builder;

use DNABeast\BladeImageCrop\TempStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Log;

class IM_WebPBuilder extends ImageTypeBuilder
{
	public $originPath;
	public $image;
	public $tempFilePath;

	public function __construct($originPath)
	{
		$this->originPath = $originPath;
		$this->image = $this->makeImage();
		$this->tempFilePath = null;
	}

	public function makeImage(){
		Log::info('Trying to make. '. $this->originPath);
		$this->tempFilePath = TempStorage::fire( Storage::disk(config('bladeimagecrop.disk'))->url($this->originPath) );
		Log::info('Temp Path. '. $this->tempFilePath);
		$image = new Imagick($this->tempFilePath);
		return $image;
	}

	public function resize($options){
		Log::info('Trying to resize. '. $this->originPath);

		if ($this->image->getNumberImages() == 0){
			Log::info('Image is somehow missing. '. $this->originPath);
			return null;
		}
		$this->image->autoOrient();
		$this->image->cropImage( $options['cropWidth'], $options['cropHeight'], $options['x'], $options['y'] );
		$this->image->resizeImage( $options['targetWidth'], $options['targetHeight'], 7, 1 );
		return $this;
	}

	public function save($destinationPath){
		Log::info('Trying to save. '. $this->originPath);

		if ($this->image->getNumberImages() == 0){
			Log::info('Image is somehow missing. '. $this->originPath);
			return null;
		}
		$this->image->setImageFormat( 'webp');
		$this->image->setImageCompressionQuality( 80 );
		Storage::disk( config('bladeimagecrop.disk') )->put($destinationPath, $this->image);
		$this->image->clear();
		if ($this->tempFilePath && file_exists($this->tempFilePath)) {
			unlink($this->tempFilePath);
		}
	}

}
