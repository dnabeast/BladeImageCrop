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
	private Helper $helper;

	public function __construct(Helper $helper)
	{
		$this->disk = Storage::disk(config('bladeimagecrop.disk'));
		$this->helper = $helper;
	}

	public function fire($originUrl, $dimensions, $offset = ['x' => 50, 'y' => 50], $format = 'jpg')
	{
		$uri = str($originUrl)->toUri();
		$originPath = $uri->path();

		$newImageUrl = $this->updateUrl($uri->host() . '/' . $originPath, $dimensions, $offset, $format);;

		if (config('bladeimagecrop.update_legacy_image_names') == 'true') {
			$oldUblockUnfriendlyUrl = Str::of($newImageUrl)->replaceMatches('/bic_(\d*x\d*_\d*_\d*\.\w{1,6})/', function (array $matches) {
				return $matches[1];
			})->toString();

			if ($this->helper->cachedFiles()->contains($oldUblockUnfriendlyUrl)) {
				$this->disk->move($oldUblockUnfriendlyUrl, $newImageUrl);
			}
		}

		if ($this->helper->cachedFiles()->contains($newImageUrl)) {
			return 'IMAGE_NOT_FOUND__' . $originUrl;
			return $this->helper->cachedUrl() . $newImageUrl;
		}

		$imageExists = $this->alterImage($originUrl, $dimensions, $offset, $format);
		if (!$imageExists) { return 'IMAGE_NOT_FOUND__'.$originUrl;}

		return $originUrl;
	}

	public function updateUrl($url, $dimensions, $offset, $format)
	{
		$segments = collect(explode('/', $url));
		$filename = $segments->pop();

		$path = '/bic/' . $segments->implode('/')
			. ($segments->count() ? '/' : '') . str_replace('.', '_', $filename)
			. '/bic_' . implode('x', $dimensions)
			. '_' . implode('_', $offset)
			. '.' . $format;

		return trim($path, '/');

	}

	public function alterImage($originUrl, $dimensions, $offset, $format)
	{

		try {
			$data = Cache::remember('bic_props_' . $originUrl, now()->addMinutes(3), function () use ($originUrl) {
				return getimagesize($originUrl);
			});
		} catch (Exception $e) {
			Log::alert($e->getMessage());
			return false;
		}

		$options = $this->options($data, $dimensions, $offset);
		$originPath = str($originUrl)->toUri()->host() . '/' . str($originUrl)->toUri()->path();

		$destinationPath = $this->updateUrl($originPath, $dimensions, $offset, $format);;

		dispatch(
			new ProcessImageJob(
				$originUrl, $format, $options, $destinationPath
			)
		);

		return true;

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

		if (@is_array(Cache::remember('bic_props_' . $this->disk->path($path), now()->addMinutes(3), function () use ($path) {
			return getimagesize($this->disk->url($path));
		}))) {
			return false;
		}

		return true;
	}


}
