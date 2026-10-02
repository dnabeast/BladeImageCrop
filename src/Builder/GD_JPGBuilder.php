<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;

class GD_JPGBuilder extends ImageTypeBuilder
{
		public $imageString;
        public $image;

		public function __construct($imageString)
		{
			$this->imageString = $imageString;
			$this->image = $this->makeImage();
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

			$image_destination = imagecreatetruecolor($options['targetWidth'],$options['targetHeight']);
			imagecopyresampled($image_destination, $this->image, 0,0,0,0, $options['targetWidth'], $options['targetHeight'], $options['cropWidth'], $options['cropHeight']);
			$this->image = $image_destination;
			return $this;
		}

		public function save($path){
			ob_start();
				imagejpeg($this->image, null, 75);
				$data = ob_get_contents();
			ob_end_clean();

			return Storage::disk( config('bladeimagecrop.disk') )->put($path, $data);
		}

	}
