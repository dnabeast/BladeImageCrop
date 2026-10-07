<?php

namespace DNABeast\BladeImageCrop;

use DNABeast\BladeImageCrop\Jobs\ProcessImage;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

class BladeImageCrop
{

	public function fire($path, $dimensions, $offset = ['x'=>50, 'y'=>50], $format = 'jpg')
	{

		if ($this->fileNotImage($path)){
			if (!\App::environment(['local'])) {
				return 'IMAGE_NOT_FOUND';
			}
			return 'IMAGE_NOT_FOUND-'.Storage::disk( config('bladeimagecrop.disk') )->path($path);
		}

		$newImageUrl = $this->updateUrl($path, $dimensions, $offset, $format);

		$fixedNewImageUrl = parse_url( Storage::disk(config('bladeimagecrop.disk') )->url( $newImageUrl ) )['path'];

		$oldUblockUnfriendlyUrl = Str::of($newImageUrl)->replaceMatches('/bic_(\d*x\d*_\d*_\d*\.\w{1,6})/', function(array $matches){
			return $matches[1];
		});

		if (Storage::disk( config('bladeimagecrop.disk') )->has($oldUblockUnfriendlyUrl)){
			Storage::disk( config('bladeimagecrop.disk') )->move($oldUblockUnfriendlyUrl, $newImageUrl);
		}

		if (Storage::disk( config('bladeimagecrop.disk') )->has($newImageUrl)){
			return Storage::disk(config('bladeimagecrop.disk') )->url( $newImageUrl );
		}

		$this->alterImage($path, $dimensions, $offset, $format);

        return Storage::disk(config('bladeimagecrop.disk') )->url( $path );
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
		}

		if(@is_array(getimagesize( Storage::disk( config('bladeimagecrop.disk') )->url($url) ))){
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

	public function alterImage($path, $dimensions, $offset, $format)
	{

		$heldImagePath = Storage::disk( config('bladeimagecrop.disk') )->path($path);

		try{
			$data = getimagesize( $heldImagePath );
		} catch (Exception $e){
			return;
		}

		$options = $this->options($data, $dimensions, $offset);

		$uri = $this->updateUrl($path, $dimensions, $offset, $format);


            new ProcessImage(
                $heldImagePath, $format, $options,$uri
            );

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
