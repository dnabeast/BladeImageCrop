<?php

namespace DNABeast\BladeImageCrop\Jobs;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessImageJob implements ShouldQueue
{
	use Dispatchable, Queueable;

	public $originUrl;
	public $format;
	public $options;
	public $destinationPath;

	public function __construct($originUrl, $format, $options, $destinationPath)
	{
		$this->originUrl = $originUrl;
		$this->format = $format;
		$this->options = $options;
		$this->destinationPath = $destinationPath;
	}

	public function handle(): void
	{
		Log::info('Processing image: ' . $this->originUrl);
		(new ImageBuilder($this->originUrl, $this->format))->resize($this->options)->save($this->destinationPath);
	}
}
