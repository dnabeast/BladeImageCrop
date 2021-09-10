<?php

namespace DNABeast\BladeImageCrop;

use Davidcb\LaravelShortPixel\Facades\LaravelShortPixel;
use Illuminate\Support\Facades\Storage;

class BladeImageCrop
{

	public function fire($url, $dimensions, $offset = ['x'=>50, 'y'=>50], $format = 'jpg')
	{

		$url = trim($url, "/");

		if (!Storage::disk( config('bladeimagecrop.disk') )->has($url)){
			if (!\App::environment(['local'])) {
				return 'IMAGENOTFOUND';
			}
			return 'IMAGENOTFOUND-'.Storage::disk( config('bladeimagecrop.disk') )->path($url);
		}

		$newImageUrl = $this->updateUrl($url, $dimensions, $offset, $format);

		if (Storage::disk( config('bladeimagecrop.disk') )->has($newImageUrl)){
			return $newImageUrl;
		}

		$this->alterImage($url, $dimensions, $offset, $format);

		return $newImageUrl;
	}

	public function updateUrl($url, $dimensions, $offset, $format)
	{

		$segments = collect(explode('/',$url));
		$filename = $segments->pop();

		$path = '/'.$segments->implode('/')
		.'/'.str_replace('.', '_', $filename)
		.'/'.implode('x', $dimensions)
		.'_'.implode('_', $offset)
		.'.'.$format;

		return str_replace("//", "/", $path);

	}

	public function alterImage($url, $dimensions, $offset, $format)
	{

		$imageString = Storage::disk( config('bladeimagecrop.disk') )->get($url);
		$data = getimagesizefromstring($imageString);


		$originalWidth = $data[0];
		$originalHeight = $data[1];
		$originalRatio = $originalWidth/$originalHeight;

		$newRatio = $dimensions['width']/$dimensions['height'];

		$maxOriginalWidth  = (50-abs($offset['x']-50))/100*$originalWidth*2;
		$maxOriginalHeight = (50-abs($offset['y']-50))/100*$originalHeight*2;


		// There are two possibilities. When the crop is wide enough to reach the edge it's not tall enough to reach to top/bottom OR vise-verse
		if ($maxOriginalWidth < $maxOriginalHeight*$newRatio){
			$newWidth = $maxOriginalWidth;
			$newHeight = $maxOriginalWidth/$newRatio;
		} else {
			$newWidth = $maxOriginalHeight*$newRatio;
			$newHeight = $maxOriginalHeight;
		}


		$newX = (int) round($originalWidth *($offset['x']/100) - ($newWidth/2));
		$newY = (int) round($originalHeight*($offset['y']/100) - ($newHeight/2));

		$options = ['x' => $newX, 'y' => $newY, 'width' => (int) round($newWidth), 'height' => (int) round($newHeight)];


		$originalImage = imagecreatefromstring($imageString);
		$image = imagecrop($originalImage, $options);

		$image_destination = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
		imagecopyresampled($image_destination, $image, 0,0,0,0, $dimensions['width'], $dimensions['height'], $newWidth, $newHeight);

		$uri = $this->updateUrl($url, $dimensions, $offset, $format);

		if (config('bladeimagecrop.text_labels')){
			$text_color = imagecolorallocate($image_destination, 0, 0, 0);
			imagestring($image_destination, 1, 4, 6, $uri , $text_color);
			$text_color = imagecolorallocate($image_destination, 255, 255, 255);
			imagestring($image_destination, 1, 5, 5, $uri, $text_color);
		}

		(new ImageBuilder)->create($image_destination, $uri, $format);

		imagedestroy($originalImage);
	}


}
