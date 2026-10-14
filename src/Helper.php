<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Helper
{

	public function cachedFiles()
	{
		return collect(Cache::remember('bic_all_files', now()->addSeconds((int) config('bladeimagecrop.cache_reset')), function() {
			\Log::debug('cachedFiles start time'. microtime(true));
			$allFiles = Storage::disk(config('bladeimagecrop.disk'))->allFiles('bic');
			\Log::debug('Cache miss', $allFiles);
			\Log::debug('cachedFiles end time'. microtime(true));

			return $allFiles;
		}));
	}

	public function cachedFileExists($path){
		return Cache::remember('bic_file_'.$path, now()->addDay(), function() use ($path) {
			return $this->cachedFiles()->contains($path);
		});
	}

	public function cachedUrl(){
		return Cache::remember('bic_disk_url', now()->addSeconds((int) config('bladeimagecrop.cache_reset')), function() {
			return Storage::disk(config('bladeimagecrop.disk'))->url('/');
		});
	}
}