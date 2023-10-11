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
		$formattedFileName = $this->src->slug.'.'.$this->src->explode('.')->last();

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
		} catch (\Exception $e) {
			return 'FILE NOT FOUND';
		}

		$this->storageDisk->put('blade_image_crop_holding/'.$formattedFileName, $file);

		return 'blade_image_crop_holding/'.$formattedFileName;
	}

}
