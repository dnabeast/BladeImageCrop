<?php

namespace DNABeast\BladeImageCrop;

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

		if (config('bladeimagecrop.remove_domain')) {
			$workingSrc = $this->src->replaceMatches("/^https?:\/\/.*?\//", "")->slug();
		} else {
			$workingSrc = $this->src->slug();
		}

		$formattedFileName = $workingSrc . '.' . $extension;

		// if file exists then return it
		if ($this->storageDisk->exists('blade_image_crop_holding/' . $formattedFileName)) {
			return 'blade_image_crop_holding/' . $formattedFileName;
		}

		try {
			if (config('bladeimagecrop.compress_held_image') == 'true' ?? false) {
				if (extension_loaded('imagick')) {
					$this->holdFileWithImageMagick($formattedFileName);
				} else {
					try {
						$this->holdFileWithGDLibrary($extension, $formattedFileName);
					} catch (\Exception $e) {
						\Log::error('GD Library failed. Reduce Image size or install Imagick. ' . $formattedFileName);
						throw new \Exception('GD Library failed.');
					}
				}
			} else {
				$file = Http::withOptions(['stream' => true])->get($this->src);

				if ($file->failed()) {
					return 'FILE NOT FOUND';
				}
				$this->storageDisk->put('blade_image_crop_holding/' . $formattedFileName, $file);
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

		$tempFilePath = tempnam(sys_get_temp_dir(), 'img_');
		$response = Http::withOptions(['stream' => true])->get($this->src);

		$inputStream = fopen($tempFilePath, 'w+');
		stream_copy_to_stream($response->toPsrResponse()->getBody()->detach(), $inputStream);
		fclose($inputStream);

		$image = new Imagick($tempFilePath);

		$image->autoOrient();
		$image->setImageCompressionQuality(80);
		$this->storageDisk->put('blade_image_crop_holding/' . $formattedFileName, $image->getImageBlob());
		$image->clear();

		if (file_exists($tempFilePath)) {
			unlink($tempFilePath);
		}

	}

	/**
	 * @param string $extension
	 * @param string $formattedFileName
	 * @return void
	 */
	public function holdFileWithGDLibrary(string $extension, string $formattedFileName): void
	{
		$body = Http::withOptions(['stream' => true])->get($this->src)->body();

		$image = @imagecreatefromstring($body);

		if ($image) {
			$outputStream = fopen('php://temp', 'w+');

			if ($extension == 'jpg' || $extension == 'jpeg') {
				imagejpeg($image, $outputStream, 95);
			} elseif ($extension == 'png') {
				imagepng($image, $outputStream, 9);
			} elseif ($extension == 'webp') {
				imagewebp($image, $outputStream, 95);
			}
			rewind($outputStream);
			$this->storageDisk->put('blade_image_crop_holding/' . $formattedFileName, stream_get_contents($outputStream));
			fclose($outputStream);
			imagedestroy($image);

		}
	}

}
