<?php

namespace DNABeast\BladeImageCrop;

use Davidcb\LaravelShortPixel\Facades\LaravelShortPixel;
use DNABeast\BladeImageCrop\Jobs\ProcessImage;
use Exception;
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
				return 'IMAGE_NOT_FOUND';
			}
			return 'IMAGE_NOT_FOUND-'.Storage::disk( config('bladeimagecrop.disk') )->path($url);
		}

		$newImageUrl = $this->updateUrl($url, $dimensions, $offset, $format);

		$fixedNewImageUrl = parse_url( Storage::disk(config('bladeimagecrop.disk') )->url( $newImageUrl ) )['path'];

		$oldUblockUnfriendlyUrl = str($newImageUrl)->replaceMatches('/bic_(\d*x\d*_\d*_\d*\.\w{1,6})/', function(array $matches){
			return $matches[1];
		})->value();


		if (Storage::disk( config('bladeimagecrop.disk') )->has($oldUblockUnfriendlyUrl)){
			Storage::disk( config('bladeimagecrop.disk') )->move($oldUblockUnfriendlyUrl, $newImageUrl);
		}

		if (Storage::disk( config('bladeimagecrop.disk') )->has($newImageUrl)){
			return $fixedNewImageUrl;
		}

        $this->alterImage($url, $dimensions, $offset, $format);

        return parse_url( Storage::disk(config('bladeimagecrop.disk') )->url( $url ) )['path'];
	}

	public function fileNotImage($url){
		$disk = Storage::disk( config('bladeimagecrop.disk') );

		if (method_exists($disk, 'fileExists')){
			$fileExists = $disk->fileExists($url);
		} else {
			$fileExists = $disk->has($url);
		}

		if (!$fileExists){
			return true;
		}

		if ( pathinfo(Storage::disk( config('bladeimagecrop.disk') )->url($url), PATHINFO_EXTENSION) === '' ){
			return true;
		};

		if(@is_array(getimagesize($disk->path($url)))){
			return false;
		}

		return true;
	}

	public function updateUrl($url, $dimensions, $offset, $format)
	{

		$segments = collect(explode('/',$url));
		$filename = $segments->pop();

		$path = '/'.$segments->implode('/')
		.'/'.str_replace('.', '_', $filename)
		.'/bic_'.implode('x', $dimensions)
		.'_'.implode('_', $offset)
		.'.'.$format;

		return  trim($path, '/');

	}

	public function alterImage($url, $dimensions, $offset, $format)
	{

		$blob = Storage::disk( config('bladeimagecrop.disk') )->get($url);

		try{
			$data = getimagesizefromstring($blob);
		} catch (Exception $e){
			return;
		}

		$options = $this->options($data, $dimensions, $offset);

		$uri = $this->updateUrl($url, $dimensions, $offset, $format);

        dispatch(
            new ProcessImage(
                $url, $format, $options,$uri
            ));

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
