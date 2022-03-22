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
		$src = trim($src, '/');

		if (!config('bladeimagecrop.images_from_public_path')){
			return $src;
		}

		$diskUrl = Storage::disk( config('bladeimagecrop.disk') )->url('/');
		$diskDirectory = trim( str_replace( trim( config('app.env') , '/' ), '', $diskUrl), '/');

		if ( strpos( $src, $diskDirectory ) === 0 ) {
			return trim( substr_replace($src, '', 0, strlen($diskDirectory)), '/');
		}

		return $src;
	}

}
