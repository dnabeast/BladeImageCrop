<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Log;

class TempStorage
{
	public static function fire($url){

		$tempFilePath = tempnam(sys_get_temp_dir(), 'img_');
		$response = Http::withOptions(['stream' => true])->get($url);

		if ($response->failed()) {
			Log::error('HTTP call failed ' . $url);
			return;
		}

		$inputStream = fopen($tempFilePath, 'w+');
		stream_copy_to_stream($response->toPsrResponse()->getBody()->detach(), $inputStream);
		fclose($inputStream);
		return $tempFilePath;
	}
}