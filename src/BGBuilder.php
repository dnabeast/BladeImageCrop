<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\Component;


class BGBuilder extends BGTypeBuilder
{
	public $src;

	public function __construct($src)
	{
		$this->src = $src;
		$this->uri = new UriHelper;
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
			$imageString = $this->uri->file( $this->src );
		} catch (\Exception $e) {
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
