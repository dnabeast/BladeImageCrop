<?php

namespace DNABeast\BladeImageCrop\Builder;

use Illuminate\Support\Facades\Storage;

class GD_WebPBuilder extends ImageTypeBuilder
{
	public $imageString;
    public $image;

	public function __construct($imageString)
	{
		$this->imageString = $imageString;
		$this->image = $this->makeImage();
	}


	// if (config('bladeimagecrop.text_labels')){
	// 	$text_color = imagecolorallocate($image_destination, 0, 0, 0);
	// 	imagestring($image_destination, 1, 4, 6, $uri , $text_color);
	// 	$text_color = imagecolorallocate($image_destination, 255, 255, 255);
	// 	imagestring($image_destination, 1, 5, 5, $uri, $text_color);
	// }



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
			imagewebp($this->image, null, 75);
			$data = ob_get_contents();
		ob_end_clean();
		return Storage::disk( config('bladeimagecrop.disk') )->put($path, $data);
	}

}
