<?php

namespace DNABeast\BladeImageCrop\Jobs;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessImageJob implements ShouldQueue, ShouldBeUnique
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

	public function uniqueId(): string
	{
		return $this->destinationPath;
	}

	public function handle(): void
	{
		(new ImageBuilder($this->originUrl, $this->format))->resize($this->options)->save($this->destinationPath);
	}
}
