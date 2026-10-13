<?php

namespace DNABeast\BladeImageCrop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Helper
{
	protected $disk;

	public function __construct()
	{
		$this->disk = Storage::disk(config('bladeimagecrop.disk'));
	}

	public function cachedFiles()
	{
		return collect(Cache::remember('bic_all_files', now()->addSeconds((int) config('bladeimagecrop.cache_reset')), function() {
			\Log::debug('Cache miss',$this->disk->allFiles('bic'));
			return $this->disk->allFiles('bic');
		}));
	}

	public function cachedUrl(){
		return Cache::remember('bic_disk_url', now()->addSeconds((int) config('bladeimagecrop.cache_reset')), function() {
			return $this->disk->url('/');
		});
	}
}