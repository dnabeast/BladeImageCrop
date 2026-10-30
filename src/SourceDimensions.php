<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Width and height of an original image, so width only images can still get a height attribute.
 * Only reads the image header, and only from the public folder or a local source disk. Remote disks (S3 etc.) are never read at render time.
 */
class SourceDimensions
{
	/**
	 * [width, height] of the image on the source, or null when it isn't local, doesn't exist or isn't an image.
	 */
	public function get($sourcePath, $source = 'disk')
	{
		$file = $this->localFile($sourcePath, $source);

		if (!$file) {
			return null;
		}

		// The modified time is in the key so a replaced image is measured again
		return Cache::rememberForever('bladeimagecrop.dimensions.' . md5($file . '|' . filemtime($file)), function () use ($file) {
			$size = @getimagesize($file);

			return $size ? [$size[0], $size[1]] : null;
		});
	}

	public function heightFor($sourcePath, $width, $source = 'disk')
	{
		$dimensions = $this->get($sourcePath, $source);

		return $dimensions ? (int) round($width * $dimensions[1] / $dimensions[0]) : null;
	}

	protected function localFile($sourcePath, $source)
	{
		if ($source === 'public') {
			return is_file(public_path($sourcePath)) ? public_path($sourcePath) : null;
		}

		$disk = Storage::disk(config('bladeimagecrop.source_disk'));

		if (!$disk->getAdapter() instanceof LocalFilesystemAdapter || !$disk->exists($sourcePath)) {
			return null;
		}

		return $disk->path($sourcePath);
	}
}
