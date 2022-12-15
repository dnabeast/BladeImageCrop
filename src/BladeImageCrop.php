<?php

namespace DNABeast\BladeImageCrop;

use Davidcb\LaravelShortPixel\Facades\LaravelShortPixel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Dimensions;
use Imagick;

class BladeImageCrop
{

	public function fire($url, $dimensions, $offset = ['x'=>50, 'y'=>50], $format = 'jpg')
	{
		if ($this->fileNotImage($url)){
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

	public function fileNotImage($url){
		$disk = Storage::disk( config('bladeimagecrop.disk') );

		if (method_exists($disk, 'fileExists')){
			$fileExists = $disk->fileExists($url);
		} else {
			$fileExists = $disk->has($url);
		}

		return !$fileExists
			|| !in_array(
				Storage::disk( config('bladeimagecrop.disk') )->mimeType($url),
				['image/jpeg', 'image/png', 'image/webp']
			)
			|| pathinfo(Storage::disk( config('bladeimagecrop.disk') )->url($url), PATHINFO_EXTENSION) === '';
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

		return parse_url( Storage::disk(config('bladeimagecrop.disk') )->url( trim($path, '/') ))['path'];
	}

	public function alterImage($url, $dimensions, $offset, $format)
	{

		$blob = Storage::disk( config('bladeimagecrop.disk') )->get($url);

		$data = getimagesizefromstring($blob);

		$options = $this->options($data, $dimensions, $offset);

		$uri = $this->updateUrl($url, $dimensions, $offset, $format);

		(new ImageBuilder($blob, $format))->resize($options)->save($uri);

	}

	public function options($data, $dimensions, $offset){

		$dimensions['width'] = $dimensions['width']??$dimensions[0];
		$dimensions['height'] = $dimensions['height']??$dimensions[1];

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

		$biggerThanOriginal = $dimensions['width'] > (int) round($newWidth) || $dimensions['height'] > (int) round($newHeight);

		$targetWidth = $biggerThanOriginal?round($newWidth):$dimensions['width'];
		$targetHeight = $biggerThanOriginal?round($newHeight):$dimensions['height'];

		return [
			'x' => $newX,
			'y' => $newY,
			'cropWidth' => (int) round($newWidth),
			'cropHeight' => (int) round($newHeight),
			'targetWidth' => (int) $targetWidth,
			'targetHeight' => (int) $targetHeight,
		];
	}


}
