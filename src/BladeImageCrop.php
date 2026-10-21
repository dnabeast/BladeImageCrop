<?php

namespace DNABeast\BladeImageCrop;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;
use DNABeast\BladeImageCrop\Jobs\ProcessImageJob;

class BladeImageCrop
{
	private Helper $helper;

	public function __construct(Helper $helper)
	{
		$this->helper = $helper;
	}

	public function fire($originUrl, $dimensions, $offset = ['x' => 50, 'y' => 50], $format = 'jpg')
	{
		$newImageUrl = $this->updatePath( $originUrl, $dimensions, $offset, $format);
		if (config('bladeimagecrop.update_legacy_image_names') == 'true') {
			$oldUblockUnfriendlyUrl = Str::of($newImageUrl)->replaceMatches('/bic_(\d*x\d*_\d*_\d*\.\w{1,6})/', function (array $matches) {
				return $matches[1];
			})->toString();

			if ($this->helper->cachedFileExists($oldUblockUnfriendlyUrl)) {
				Storage::disk(config('bladeimagecrop.disk'))->move($oldUblockUnfriendlyUrl, $newImageUrl);
			}
		}

		if ($this->helper->cachedFileExists($newImageUrl)) {
			return $this->helper->cachedUrl() . $newImageUrl;
		}

		$imageExists = $this->alterImage($originUrl, $dimensions, $offset, $format);
		if (!$imageExists) {return 'IMAGE_NOT_FOUND__' . $originUrl;}

		return $originUrl;
	}

	public function updatePath($originUrl, $dimensions, $offset, $format)
	{

		$uri = str($originUrl)->toUri();
		$originPath = $uri->path();

		if (config('bladeimagecrop.sort_by_domain_name') == 'true') {
			$originPath = $uri->host() . '/' .$originPath;
		}

		$segments = collect(explode('/', $originPath));
		$filename = $segments->pop();

		$destinationPath = '/bic/' . $segments->implode('/')
			. ($segments->count() ? '/' : '') . str_replace('.', '_', $filename)
			. '/bic_' . implode('x', $dimensions)
			. '_' . implode('_', $offset)
			. '.' . $format;

		return trim($destinationPath, '/');

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

		$destinationPath = $this->updatePath($originUrl, $dimensions, $offset, $format);;

		if ( config('bladeimagecrop.schedule_tasks')){
			$this->createTask([$originUrl, $format, $options, $destinationPath]);
		} else {
			ProcessImageJob::dispatch($originUrl, $format, $options, $destinationPath);
		}

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

	public function createTask($data){
		if (!DB::table('bladeimagecrop_tasks')->where('task_properties', json_encode($data))->exists()) {
			DB::table('bladeimagecrop_tasks')
				->insert([
					'task_properties' => json_encode($data),
					'created_at' => now(),
					'updated_at' => now()
				]);
		}
	}


}
