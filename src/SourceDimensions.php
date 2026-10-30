<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Width and height of an original image, so width only images can still get a height attribute.
 * Only reads the image header, and only from a local source disk. Remote disks (S3 etc.) are never read at render time.
 */
class SourceDimensions
{
	/**
	 * [width, height] of the image on the source disk, or null when it isn't local, doesn't exist or isn't an image.
	 */
	public function get($sourcePath)
	{
		$disk = Storage::disk(config('bladeimagecrop.source_disk'));

		if (!$disk->getAdapter() instanceof LocalFilesystemAdapter || !$disk->exists($sourcePath)) {
			return null;
		}

		$file = $disk->path($sourcePath);

		// The modified time is in the key so a replaced image is measured again
		return Cache::rememberForever('bladeimagecrop.dimensions.' . md5($sourcePath . '|' . filemtime($file)), function () use ($file) {
			$size = @getimagesize($file);

			return $size ? [$size[0], $size[1]] : null;
		});
	}

	public function heightFor($sourcePath, $width)
	{
		$dimensions = $this->get($sourcePath);

		return $dimensions ? (int) round($width * $dimensions[1] / $dimensions[0]) : null;
	}
}
