<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Imagick;


class BGBuilder extends BGTypeBuilder
{
	public $src;

	public function __construct($src)
	{
		$this->src = $src;
	}

	public function make(){
		$base64 = Cache::rememberForever(Str::slug($this->src), function(){
			return $this->resizedImage();
		});

		return "style=\"background-size: 100% 100%; background-image: url('data:image/png;base64,$base64')\"";
	}

	public function resizedImage(){
		$newWidth = 4;
		$newHeight = 4;
		try {
			$imageString = Storage::disk( config('bladeimagecrop.disk') )->get( $this->src );
		} catch (\Exception $e) {
			return 'MissingBGImage';
		}
		if (!$imageString) {
			return 'MissingBGImage';
		}

		$newImage = imagecreatetruecolor($newWidth, $newHeight);
		$image = imagecreatefromstring($imageString);
		imagecopyresampled( $newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($image), imagesy($image) );

		ob_start();
		imagepng($newImage);
		$data =  ob_get_clean();

		imagedestroy($image);
		imagedestroy($newImage);

		return base64_encode($data);


	}

}
