<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Storage;


class UriHelper
{

	public function directory($src){
		return dirname( Storage::disk( config('bladeimagecrop.disk') )->path($src));
	}

	public function filename($src){
		return pathinfo($src, PATHINFO_FILENAME);
	}

	public function path($src){
		return Storage::disk( config('bladeimagecrop.disk') )->path($src);
	}

	public function file($src){
		return Storage::disk( config('bladeimagecrop.disk') )->get($src);
	}

	public function trim($src){
		if (!config('bladeimagecrop.images_from_public_path')){
			return trim($src, '/');
		}
		$url = Storage::disk(config('bladeimagecrop.disk') )->url('/');
		$diskDirectory = str_replace(env('APP_URL'), "", $url);
		$diskDirectory = ltrim($diskDirectory, '/');

		$src = trim($src, '/');

		return ltrim($src, $diskDirectory);

	}
}
