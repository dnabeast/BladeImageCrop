<?php

namespace DNABeast\BladeImageCrop\Jobs;

use DNABeast\BladeImageCrop\ImageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $url;
    public $format;
    public $options;
    public $uri;

    /**
     * Create a new job instance.
     */
    public function __construct($url, $format,$options,$uri) {
        $this->url = $url;
        $this->format = $format;
        $this->options = $options;
        $this->uri = $uri;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $blob = Storage::disk( config('bladeimagecrop.disk') )->get($this->url);
        (new ImageBuilder($blob, $this->format))->resize($this->options)->save($this->uri);
    }
}
