<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;
use Imagick;

class IM_JPGBuilder extends ImageTypeBuilder
{
		public $originPath;
        public $image;

		public function __construct($originPath)
		{
			$this->originPath = $originPath;
			$this->image = $this->makeImage();
		}

	public function makeImage(){
		$image = new Imagick($this->originPath);
		$image->autoOrient();
		return $image;
	}

		public function resize($options){
			$this->image->cropImage( $options['cropWidth'], $options['cropHeight'], $options['x'], $options['y'] );
			$this->image->resizeImage( $options['targetWidth'], $options['targetHeight'], 7, 1 );
			return $this;
		}

		public function save($destinationPath){
			$this->image->setImageFormat( 'jpg');
			$this->image->setImageCompressionQuality(75);
			return Storage::disk( config('bladeimagecrop.disk') )->put($destinationPath, $this->image);
		}

	}

