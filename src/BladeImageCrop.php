<?php

namespace DNABeast\BladeImageCrop;

use Davidcb\LaravelShortPixel\Facades\LaravelShortPixel;

use Illuminate\Support\Facades\Storage;

class BladeImageCrop
{

	public function fire($url, $dimensions, $offset = [50, 50])
	{

		if (!Storage::disk('uploads')->has($url)){
			return 'imageNotFound';
		}

		$newImageUrl = $this->updateUrl($url, $dimensions, $offset);

		if (Storage::disk('uploads')->has($newImageUrl)){
			return '/uploads/'.$newImageUrl;
		}

		$this->alterImage($url, $dimensions, $offset);

		$this->dispatchCompression($newImageUrl);

		return '/uploads/'.trim($newImageUrl, '/');
	}

	public function updateUrl($url, $dimensions, $offset)
	{
		$segments = collect(explode('/',$url));
		$filename = $segments->pop();

		return $segments->implode('/')
		.'/'.str_replace('.', '_', $filename)
		.'/'.implode('x', $dimensions)
		.'_'.implode('_', $offset)
		.'.jpg';

	}

	public function alterImage($url, $dimensions, $offset)
	{

		$imageString = Storage::disk('uploads')->get($url);
		$data = getimagesizefromstring($imageString);

		$originalWidth = $data[0];
		$originalHeight = $data[1];
		$originalRatio = $originalWidth/$originalHeight;

		$newRatio = $dimensions[0]/$dimensions[1];
		$maxOriginalWidth = (50-abs($offset[0]-50))/100*$originalWidth*2;
		$maxOriginalHeight = (50-abs($offset[1]-50))/100*$originalHeight*2;

		if ($originalRatio < $newRatio){ // trim top and bottom
			$newWidth = $maxOriginalWidth;
			$newHeight = $maxOriginalWidth/$newRatio;
		} else { // trim left and right
			$newWidth = $maxOriginalHeight*$newRatio;
			$newHeight = $maxOriginalHeight;
		}


		$newX = (int) round($originalWidth*($offset[0]/100) - ($newWidth/2));
		$newY = (int) round($originalHeight*($offset[1]/100) - ($newHeight/2));

		$options = ['x' => $newX, 'y' => $newY, 'width' => (int) round($newWidth), 'height' => (int) round($newHeight)];

		$im2 = imagecreatefromstring($imageString);
		$image = imagecrop($im2, $options);
		$image_destination = imagecreatetruecolor($dimensions[0], $dimensions[1]);
		imagecopyresampled($image_destination, $image, 0,0,0,0, $dimensions[0], $dimensions[1], $newWidth, $newHeight);

		ob_start();
			imageJpeg($image_destination, null, 75);
			$data = ob_get_contents();
		ob_end_clean();

		Storage::disk('uploads')->put($this->updateUrl($url, $dimensions, $offset), $data);
		imagedestroy($im2);
	}

	public function dispatchCompression($newImageUrl)
	{

		if (config('shortpixel.active')){
			dispatch(function() use ( $newImageUrl ){
				$from = public_path('uploads/'.$newImageUrl);
				LaravelShortPixel::fromFiles($from, dirname($from));
			});
		}

	}
}
