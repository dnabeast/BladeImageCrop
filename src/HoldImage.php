<?php
namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class HoldImage
{
	public $src;
	public $storageDisk;

	public function __construct($src)
	{
		$this->src = Str::of($src);
		$this->storageDisk = Storage::disk( config('bladeimagecrop.disk') );
	}

	public function path(){
		return $this->storageDisk->path( $this->file() );
	}

	public function file(){
		$extension = strtolower($this->src->explode('.')->last());
		$formattedFileName = $this->src->slug.'.'.$extension;
		// if file exists then return it
		if ( $this->storageDisk->has( 'blade_image_crop_holding/'.$formattedFileName ) ){
			return 'blade_image_crop_holding/'.$formattedFileName;
		}

		try {
			if($this->src->startsWith('http')){
				$file = Http::get($this->src)->body();
			} else {
				$file = File::get( public_path($this->src) );
			}

			if ( config('bladeimagecrop.compress_held_image')??false ) {
				$glob = imagecreatefromstring($file);
				ob_start();
				if ($extension == 'jpg' || $extension == 'jpeg') {
					imagejpeg($glob, null, 95);
				}
				if($extension == 'png') {
					imagepng($glob);
				}
				if($extension == 'webp') {
					imagewebp($glob, null, 95);
				}
				$newFile = ob_get_contents();
				if($newFile && strlen($newFile) < strlen($file)){
					$file = $newFile;
				}
				ob_end_clean();
			}
		} catch (\Exception $e) {
			return 'FILE NOT FOUND';
		}



		$this->storageDisk->put('blade_image_crop_holding/'.$formattedFileName, $file);

		return 'blade_image_crop_holding/'.$formattedFileName;
	}

}
