<?php

namespace DNABeast\BladeImageCrop;

use DNABeast\BladeImageCrop\Jobs\ProcessImageJob;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;

class BladeImageCrop
{
	private \Illuminate\Contracts\Filesystem\Filesystem $disk;

	public function __construct()
	{
		$this->disk = Storage::disk(config('bladeimagecrop.disk'));
	}

	public function fire($path, $dimensions, $offset = ['x' => 50, 'y' => 50], $format = 'jpg')
	{
//		if ($this->fileNotImage($path)) {
//			if (!\App::environment(['local'])) {
//				return 'IMAGE_NOT_FOUND';
//			}
//			return 'IMAGE_NOT_FOUND-' . $this->disk->path($path);
//		}

		$newImageUrl = $this->updateUrl($path, $dimensions, $offset, $format);

		$oldUblockUnfriendlyUrl = Str::of($newImageUrl)->replaceMatches('/bic_(\d*x\d*_\d*_\d*\.\w{1,6})/', function (array $matches) {
			return $matches[1];
		});

		if ($this->disk->has($oldUblockUnfriendlyUrl)) {
			$this->disk->move($oldUblockUnfriendlyUrl, $newImageUrl);
		}

		if ($this->disk->has($newImageUrl)) {
			Log::info($newImageUrl);
//			return 'https://fls-a2dba409-6fe0-437c-8842-cc19bc5f3571.laravel.cloud/bic/filament/315/IslandsofTahiti_BoraBora_Wedding_LeBoraBorabyPearlResorts_BBPBR---wedding---GLB-23_jpg/bic_361x90_50_50.webp';

			return $this->disk->url($newImageUrl);
		}




//		$this->alterImage($path, $dimensions, $offset, $format);

		return "";
		return $this->disk->url($path);
	}

	public function fileNotImage($path)
	{
		$disk = $this->disk;

		if (method_exists($disk, 'fileExists')) {
			$fileExists = $disk->fileExists($path);
		} else {
			$fileExists = $disk->has($path);
		}

		if (!$fileExists) {
			return true;
		}

		if (pathinfo($this->disk->url($path), PATHINFO_EXTENSION) === '') {
			return true;
		}

		if (@is_array(Cache::remember('bic_props_'. $this->disk->path($path), now()->addMinutes(3), function() use ($path){
			return getimagesize($this->disk->url($path));
		}))) {
			return false;
		}

		return true;
	}

	public function updateUrl($url, $dimensions, $offset, $format)
	{
		$segments = collect(explode('/', $url));
		$filename = $segments->pop();

		$path = '/bic/' . $segments->implode('/')
			. ($segments->count()?'/':'') . str_replace('.', '_', $filename)
			. '/bic_' . implode('x', $dimensions)
			. '_' . implode('_', $offset)
			. '.' . $format;

		return trim($path, '/');

	}

	public function alterImage($path, $dimensions, $offset, $format)
	{
		try {
			$data = Cache::remember('bic_props_'. $path, now()->addMinutes(3), function() use ($path){
				return getimagesize($this->disk->url($path));
			});
		} catch (Exception $e) {
			Log::alert($e->getMessage());
			return;
		}

		$options = $this->options($data, $dimensions, $offset);

		$uri = $this->updateUrl($path, $dimensions, $offset, $format);

		dispatch(
			new ProcessImageJob(
				$path, $format, $options, $uri
			)
		);

	}

	public function options($data, $dimensions, $offset)
	{

		$dimensions['width'] = $dimensions['width'] ?? $dimensions[0];
		$dimensions['height'] = $dimensions['height'] ?? $dimensions[1];

		$originalWidth = $data[0];
		$originalHeight = $data[1];
		$originalRatio = $originalWidth / $originalHeight;

		$newRatio = $dimensions['width'] / $dimensions['height'];

		$maxOriginalWidth = (50 - abs($offset['x'] - 50)) / 100 * $originalWidth * 2;
		$maxOriginalHeight = (50 - abs($offset['y'] - 50)) / 100 * $originalHeight * 2;

		// There are two possibilities. When the crop is wide enough to reach the edge it's not tall enough to reach to top/bottom OR vise-verse
		if ($maxOriginalWidth < $maxOriginalHeight * $newRatio) {
			$newWidth = $maxOriginalWidth;
			$newHeight = $maxOriginalWidth / $newRatio;
		} else {
			$newWidth = $maxOriginalHeight * $newRatio;
			$newHeight = $maxOriginalHeight;
		}

		$newX = (int)round($originalWidth * ($offset['x'] / 100) - ($newWidth / 2));
		$newY = (int)round($originalHeight * ($offset['y'] / 100) - ($newHeight / 2));

		$biggerThanOriginal = $dimensions['width'] > (int)round($newWidth) || $dimensions['height'] > (int)round($newHeight);

		$targetWidth = $biggerThanOriginal ? round($newWidth) : $dimensions['width'];
		$targetHeight = $biggerThanOriginal ? round($newHeight) : $dimensions['height'];

		return [
			'x' => $newX,
			'y' => $newY,
			'cropWidth' => (int)round($newWidth),
			'cropHeight' => (int)round($newHeight),
			'targetWidth' => (int)$targetWidth,
			'targetHeight' => (int)$targetHeight,
		];
	}


}
