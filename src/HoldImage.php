<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;


class HoldImage
{
	public $src;
	public $storageDisk;

	public function __construct($src)
	{
		$this->src = Str::of($src);
		$this->storageDisk = Storage::disk(config('bladeimagecrop.disk'));
	}

	public function path()
	{
		return $this->storageDisk->path($this->file());
	}


	public function file()
	{

		$extension = strtolower($this->src->explode('.')->last());
		$formattedFileName = $this->src->slug . '.' . $extension;
		// if file exists then return it

		if ($this->storageDisk->exists('blade_image_crop_holding/' . $formattedFileName)) {
			return 'blade_image_crop_holding/' . $formattedFileName;
		}

		try {
			if (config('bladeimagecrop.compress_held_image') ?? false) {
				if (extension_loaded('imagick')) {
					$this->holdFileWithImageMagick($formattedFileName);
				} else {
					$this->holdFileWithGDLibrary($extension, $formattedFileName);
				}
			} else {
				$this->storageDisk->put('blade_image_crop_holding/' . $formattedFileName, File::get(public_path($this->src)));
				return 'blade_image_crop_holding/' . $formattedFileName;
			}
		} catch (\Exception $e) {
			return 'FILE NOT FOUND';
		}

		return 'blade_image_crop_holding/' . $formattedFileName;
	}

	/**
	 * @param string $formattedFileName
	 * @return void
	 * @throws \ImagickException
	 */
	public function holdFileWithImageMagick(string $formattedFileName): void
	{
		if ($this->src->startsWith('http')) {
			$image = new Imagick();
			$image->readImageBlob(file_get_contents($this->src));
		} else {
			$image = new Imagick(public_path($this->src));
		}

		$image->autoOrient();
		$image->setImageCompressionQuality(85);
		$this->storageDisk->put('blade_image_crop_holding/' . $formattedFileName, $image->getImageBlob());
		$image->destroy();
	}

	/**
	 * @param string $extension
	 * @param string $formattedFileName
	 * @return void
	 */
	public function holdFileWithGDLibrary(string $extension, string $formattedFileName): void
	{
		if ($this->src->startsWith('http')) {
			$file = Http::get($this->src)->body();
		} else {
			$file = File::get(public_path($this->src));
		}

		$glob = @imagecreatefromstring($file);

		if ((bool)$glob) {
			ob_start();
			if ($extension == 'jpg' || $extension == 'jpeg') {
				imagejpeg($glob, null, 95);
			}
			if ($extension == 'png') {
				imagepng($glob);
			}
			if ($extension == 'webp') {
				imagewebp($glob, null, 95);
			}
			$newFile = ob_get_contents();
			if ($newFile && strlen($newFile) < strlen($file)) {
				$file = $newFile;
			}
			ob_end_clean();
		}
		$this->storageDisk->put('blade_image_crop_holding/' . $formattedFileName, $file);
	}

}
