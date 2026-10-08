<?php

namespace DNABeast\BladeImageCrop\Jobs;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessImage implements ShouldQueue
{
	use Queueable;


	public $path;
	public $format;
	public $options;
	public $uri;

	/**
	 * Create a new job instance.
	 */
	public function __construct($path, $format, $options, $uri)
	{
		$this->path = $path;
		$this->format = $format;
		$this->options = $options;
		$this->uri = $uri;
	}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		Log::info('Processing image: ' . $this->path);
		(new ImageBuilder($this->path, $this->format))->resize($this->options)->save($this->uri);
	}
}
